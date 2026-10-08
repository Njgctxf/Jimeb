<?php

namespace Tests\Feature;

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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Verifies directPrint bill race fixes:
 * - unique per-print filenames (never shared order-{id}.png)
 * - PrintJob created before PNG exists; desktop waits for that file
 * - upload writes exactly to the job filename
 * - pullMultiple claims atomically (no double print)
 * - image_path has a clean .png/.pdf extension (no ?v= query string)
 */
class DirectPrintBillRaceTest extends TestCase
{
    /** @var list<string> */
    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (is_string($path) && File::exists($path)) {
                File::delete($path);
            }
        }

        DB::rollBack();
        parent::tearDown();
    }

    public function test_handle_order_print_creates_unique_pending_job_before_png_exists(): void
    {
        if (! Schema::hasTable('print_jobs') || ! Schema::hasTable('orders')) {
            $this->markTestSkipped('Required tables missing');
        }

        $order = Order::with('branch.restaurant')->whereHas('branch')->latest('id')->first();
        if (! $order) {
            $this->markTestSkipped('No order available');
        }

        session([
            'restaurant' => $order->branch->restaurant,
            'branch' => $order->branch,
        ]);

        $harness = $this->makePrinterHarness();
        $harness->handleOrderPrint($order->id);

        $job = PrintJob::query()->latest('id')->first();
        $this->assertNotNull($job);
        $this->assertSame('pending', $job->status);
        $this->assertMatchesRegularExpression(
            '/^order-' . $order->id . '-[a-f0-9]+\.png$/',
            (string) $job->image_filename,
            'Expected unique per-print filename, not shared order-{id}.png'
        );
        $this->assertNotSame('order-' . $order->id . '.png', $job->image_filename);

        $fullPath = public_path(Files::UPLOAD_FOLDER . '/print/' . $job->image_filename);
        $this->assertFalse(File::exists($fullPath), 'PNG must not exist until browser upload finishes');

        $this->assertCount(1, $harness->dispatches);
        $this->assertSame('saveOrderImageFromPrint', $harness->dispatches[0]['event']);
        $this->assertSame($order->id, $harness->dispatches[0]['params'][0]);
        $this->assertSame($job->image_filename, $harness->dispatches[0]['params'][2]);

        $this->assertStringNotContainsString('?', $job->image_path);
        $this->assertMatchesRegularExpression('/\.(png|pdf)$/', $job->image_path);
    }

    public function test_two_prints_of_same_order_get_different_filenames(): void
    {
        if (! Schema::hasTable('print_jobs') || ! Schema::hasTable('orders')) {
            $this->markTestSkipped('Required tables missing');
        }

        $order = Order::with('branch.restaurant')->whereHas('branch')->latest('id')->first();
        if (! $order) {
            $this->markTestSkipped('No order available');
        }

        session([
            'restaurant' => $order->branch->restaurant,
            'branch' => $order->branch,
        ]);

        $harness = $this->makePrinterHarness();
        $harness->handleOrderPrint($order->id);
        $first = PrintJob::query()->latest('id')->first();

        $harness->resetImageFilename();
        $harness->handleOrderPrint($order->id);
        $second = PrintJob::query()->latest('id')->first();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($first->image_filename, $second->image_filename);
    }

    public function test_png_upload_writes_job_filename_then_pull_claims_once(): void
    {
        if (! Schema::hasTable('print_jobs') || ! Schema::hasTable('branches')) {
            $this->markTestSkipped('Required tables missing');
        }

        $branch = Branch::query()->first();
        if (! $branch) {
            $this->markTestSkipped('No branch available');
        }

        $orderId = (int) (Order::query()->latest('id')->value('id') ?? 1);
        $filename = 'order-' . $orderId . '-' . uniqid('', false) . '.png';

        $job = PrintJob::create([
            'image_filename' => $filename,
            'restaurant_id' => $branch->restaurant_id,
            'branch_id' => $branch->id,
            'status' => 'pending',
            'printer_id' => null,
        ]);

        $fullPath = public_path(Files::UPLOAD_FOLDER . '/print/' . $filename);
        $this->assertFalse(File::exists($fullPath));

        // Missing file => this job must not be claimed yet (other pending jobs with files may exist).
        $empty = $this->callPullMultiple($branch);
        $emptyIds = array_column($empty, 'id');
        $this->assertNotContains($job->id, $emptyIds);

        // Simulate browser upload to the exact job filename.
        $png = $this->tinyPngBase64();
        $storeRequest = Request::create('/order/png', 'POST', [
            'image_base64' => $png,
            'order_id' => $orderId,
            'filename' => $filename,
            'mono' => true,
        ]);
        $storeResponse = (new ViewPngController())->storeOrder($storeRequest);
        $storePayload = $storeResponse->getData(true);

        $this->assertTrue($storePayload['ok'] ?? false, json_encode($storePayload));
        $this->assertTrue(File::exists($fullPath), 'Upload must create the unique print file');
        $this->createdFiles[] = $fullPath;

        // First pull claims the job.
        $firstPull = $this->callPullMultiple($branch);
        $this->assertCount(1, $firstPull);
        $this->assertSame($job->id, $firstPull[0]['id']);
        $this->assertSame('printing', PrintJob::find($job->id)->status);
        $this->assertStringNotContainsString('?', $firstPull[0]['image_path'] ?? '');
        $this->assertMatchesRegularExpression('/\.(png|pdf)$/', $firstPull[0]['image_path'] ?? '');

        // Second pull must not re-emit the same job.
        $secondPull = $this->callPullMultiple($branch);
        $ids = array_column($secondPull, 'id');
        $this->assertNotContains($job->id, $ids);
    }

    public function test_png_upload_rejects_path_traversal_filename(): void
    {
        $orderId = (int) (Order::query()->latest('id')->value('id') ?? 1);
        $request = Request::create('/order/png', 'POST', [
            'image_base64' => $this->tinyPngBase64(),
            'order_id' => $orderId,
            'filename' => '../evil.png',
        ]);

        $response = (new ViewPngController())->storeOrder($request);
        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse($payload['ok'] ?? true);
    }

    private function makePrinterHarness(): object
    {
        return new class
        {
            use PrinterSetting;

            public array $dispatches = [];

            public array $alerts = [];

            protected function dispatch($event, ...$params): void
            {
                $this->dispatches[] = ['event' => $event, 'params' => $params];
            }

            protected function alert($type, $message, $options = []): void
            {
                $this->alerts[] = compact('type', 'message');
            }

            public function resetImageFilename(): void
            {
                $this->imageFilename = null;
            }
        };
    }

    private function callPullMultiple(Branch $branch): array
    {
        $controller = new PrintJobController();
        $request = Request::create('/api/print-jobs/pull-multiple', 'GET');
        $request->attributes->set('branch', $branch);
        $request->setUserResolver(fn () => null);

        // PrintJobController reads branch via $request->get('branch') from middleware merge.
        $request->merge(['branch' => $branch]);

        $response = $controller->pullMultiple($request);

        return $response->getData(true);
    }

    private function tinyPngBase64(): string
    {
        // 1x1 transparent PNG
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    }
}
