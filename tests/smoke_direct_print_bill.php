<?php

/**
 * One-off smoke runner for directPrint bill race fixes.
 * Usage: php tests/smoke_direct_print_bill.php
 */

use App\Helper\Files;
use App\Http\Controllers\PrintJobController;
use App\Http\Controllers\ViewPngController;
use App\Models\Branch;
use App\Models\Order;
use App\Models\PrintJob;
use App\Traits\PrinterSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failed = 0;
$passed = 0;

function assert_true(bool $cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        echo "PASS: {$msg}\n";
        $passed++;
    } else {
        echo "FAIL: {$msg}\n";
        $failed++;
    }
}

DB::beginTransaction();
$createdFiles = [];

try {
    $order = Order::with('branch.restaurant')->whereHas('branch')->latest('id')->first();
    assert_true((bool) $order, 'order available id=' . ($order->id ?? 'n/a'));
    if (! $order) {
        throw new RuntimeException('No order');
    }

    // printOrder() depends on restaurant()/branch() session helpers.
    session([
        'restaurant' => $order->branch->restaurant,
        'branch' => $order->branch,
    ]);

    $harness = new class
    {
        use PrinterSetting;

        public array $dispatches = [];

        protected function dispatch($event, ...$params): void
        {
            $this->dispatches[] = ['event' => $event, 'params' => $params];
        }

        protected function alert($type, $message, $options = []): void
        {
        }

        public function resetImageFilename(): void
        {
            $this->imageFilename = null;
        }
    };

    // 1) unique pending job before PNG
    $harness->handleOrderPrint($order->id);
    $job1 = PrintJob::latest('id')->first();
    assert_true($job1 && $job1->status === 'pending', 'job pending after handleOrderPrint');
    assert_true(
        (bool) preg_match('/^order-' . $order->id . '-[a-f0-9]+\.png$/', (string) $job1->image_filename),
        'unique filename=' . $job1->image_filename
    );
    assert_true($job1->image_filename !== 'order-' . $order->id . '.png', 'not using shared order-id.png');
    $path1 = public_path(Files::UPLOAD_FOLDER . '/print/' . $job1->image_filename);
    assert_true(! File::exists($path1), 'png missing until upload');
    assert_true(
        count($harness->dispatches) === 1 && $harness->dispatches[0]['event'] === 'saveOrderImageFromPrint',
        'dispatched saveOrderImageFromPrint'
    );
    assert_true(
        ($harness->dispatches[0]['params'][2] ?? null) === $job1->image_filename,
        'dispatch carries job filename'
    );
    assert_true(! str_contains($job1->image_path, '?'), 'image_path has no query string for desktop app');

    // 2) second print different filename
    $harness->resetImageFilename();
    $harness->dispatches = [];
    $harness->handleOrderPrint($order->id);
    $job2 = PrintJob::latest('id')->first();
    assert_true($job1->id !== $job2->id, 'second print creates new job');
    assert_true($job1->image_filename !== $job2->image_filename, 'second print different filename');

    // 3) upload + atomic claim
    $branch = Branch::find($order->branch_id) ?: Branch::first();
    $filename = 'order-' . $order->id . '-' . uniqid('', false) . '.png';
    $job3 = PrintJob::create([
        'image_filename' => $filename,
        'restaurant_id' => $branch->restaurant_id,
        'branch_id' => $branch->id,
        'status' => 'pending',
        'printer_id' => null,
    ]);
    $full = public_path(Files::UPLOAD_FOLDER . '/print/' . $filename);

    $reqEmpty = Request::create('/api/print-jobs/pull-multiple', 'GET', ['branch' => $branch]);
    $reqEmpty->merge(['branch' => $branch]);
    $emptyPull = (new PrintJobController())->pullMultiple($reqEmpty)->getData(true);
    $emptyIds = array_column($emptyPull, 'id');
    assert_true(! in_array($job3->id, $emptyIds, true), 'job without png is not claimed yet');

    $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    $storeReq = Request::create('/order/png', 'POST', [
        'image_base64' => $png,
        'order_id' => $order->id,
        'filename' => $filename,
        'mono' => true,
    ]);
    $storePayload = (new ViewPngController())->storeOrder($storeReq)->getData(true);
    assert_true(($storePayload['ok'] ?? false) === true, 'png upload ok: ' . json_encode($storePayload));
    assert_true(File::exists($full), 'upload wrote unique file');
    $createdFiles[] = $full;

    $req1 = Request::create('/api/print-jobs/pull-multiple', 'GET', ['branch' => $branch]);
    $req1->merge(['branch' => $branch]);
    $first = (new PrintJobController())->pullMultiple($req1)->getData(true);
    $claimed = collect($first)->firstWhere('id', $job3->id);
    assert_true($claimed !== null, 'first pull claims uploaded job');
    assert_true(PrintJob::find($job3->id)->status === 'printing', 'status printing after claim');
    assert_true(! str_contains($claimed['image_path'] ?? '', '?'), 'pulled image_path has no query string');

    $req2 = Request::create('/api/print-jobs/pull-multiple', 'GET', ['branch' => $branch]);
    $req2->merge(['branch' => $branch]);
    $second = (new PrintJobController())->pullMultiple($req2)->getData(true);
    $ids = array_column($second, 'id');
    assert_true(! in_array($job3->id, $ids, true), 'second pull does not re-claim same job');

    // 4) reject path traversal
    $badReq = Request::create('/order/png', 'POST', [
        'image_base64' => $png,
        'order_id' => $order->id,
        'filename' => '../evil.png',
    ]);
    $bad = (new ViewPngController())->storeOrder($badReq);
    assert_true($bad->getStatusCode() === 422, 'path traversal rejected with 422');

    assert_true(
        $job1->image_filename !== 'order-' . $order->id . '.png'
            && $job2->image_filename !== 'order-' . $order->id . '.png',
        'neither job uses stale shared name'
    );
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
    $failed++;
} finally {
    foreach ($createdFiles as $f) {
        if (File::exists($f)) {
            File::delete($f);
        }
    }
    DB::rollBack();
}

echo "\nResult: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
