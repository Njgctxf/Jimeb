<?php

namespace Tests\Unit;

use App\Events\ActiveWaiterRequestCreatedEvent;
use App\Events\CustomerDisplayUpdated;
use App\Events\KotUpdated;
use App\Events\NewOrderCreated;
use App\Events\OrderSuccessEvent;
use App\Events\OrderUpdated;
use App\Events\PrintJobCreated;
use App\Events\TodayOrdersUpdated;
use App\Events\TodayReservationCreatedEvent;
use App\Models\Kot;
use App\Models\Order;
use App\Models\PrintJob;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Tests\TestCase;

class PusherRealtimeBroadcastTest extends TestCase
{
    public function test_customer_display_updated_broadcasts_immediately_on_user_channel(): void
    {
        $payload = ['order_number' => '42', 'status' => 'idle'];
        $event = new CustomerDisplayUpdated($payload, 7);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertSame('customer-display.updated', $event->broadcastAs());
        $this->assertSame('customer-display-user-7', $this->channelName($event->broadcastOn()));
        $this->assertSame($payload, $event->broadcastWith()['order_data']);
        $this->assertArrayHasKey('timestamp', $event->broadcastWith());
    }

    public function test_today_orders_updated_broadcasts_immediately(): void
    {
        $event = new TodayOrdersUpdated(12);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertSame('today-orders', $this->channelName($event->broadcastOn()));
        $this->assertSame('today-orders.updated', $event->broadcastAs());
        $this->assertSame(['count' => 12], $event->broadcastWith());
    }

    public function test_today_reservations_created_broadcasts_immediately(): void
    {
        $event = new TodayReservationCreatedEvent(3);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertSame('today-reservations', $this->channelName($event->broadcastOn()));
        $this->assertSame('today-reservations.created', $event->broadcastAs());
        $this->assertSame(['count' => 3], $event->broadcastWith());
    }

    public function test_active_waiter_requests_created_broadcasts_immediately(): void
    {
        $event = new ActiveWaiterRequestCreatedEvent(4);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertSame('active-waiter-requests', $this->channelName($event->broadcastOn()));
        $this->assertSame('active-waiter-requests.created', $event->broadcastAs());
        $this->assertSame(['count' => 4], $event->broadcastWith());
    }

    public function test_order_success_broadcasts_immediately_with_order_id(): void
    {
        $order = new Order();
        $order->id = 88;
        $event = new OrderSuccessEvent($order);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertSame('order-success', $this->channelName($event->broadcastOn()));
        $this->assertSame('order-success.created', $event->broadcastAs());
        $this->assertSame(['count' => 1, 'order_id' => 88], $event->broadcastWith());
    }

    public function test_order_updated_and_created_broadcast_immediately_on_orders_channel(): void
    {
        $order = new Order([
            'formatted_order_number' => 'ORD-9',
            'order_number' => '9',
            'status' => 'kot',
        ]);
        $order->id = 9;

        $updated = new OrderUpdated($order, 'updated');
        $this->assertInstanceOf(ShouldBroadcastNow::class, $updated);
        $this->assertSame('orders', $this->channelName($updated->broadcastOn()));
        $this->assertSame('order.updated', $updated->broadcastAs());
        $this->assertSame(9, $updated->broadcastWith()['order_id']);
        $this->assertSame('updated', $updated->broadcastWith()['action']);

        $created = new NewOrderCreated($order);
        $this->assertInstanceOf(ShouldBroadcastNow::class, $created);
        $this->assertSame('orders', $this->channelName($created->broadcastOn()));
        $this->assertSame('order.created', $created->broadcastAs());
        $this->assertSame([
            'order_id' => 9,
            'order_number' => 'ORD-9',
        ], $created->broadcastWith());
    }

    public function test_kot_updated_and_print_job_created_broadcast_immediately(): void
    {
        $kot = new Kot();
        $kot->id = 5;
        $kotEvent = new KotUpdated($kot, 'status_updated');

        $this->assertInstanceOf(ShouldBroadcastNow::class, $kotEvent);
        $this->assertSame('kots', $this->channelName($kotEvent->broadcastOn()));
        $this->assertSame('kot.updated', $kotEvent->broadcastAs());

        $job = new PrintJob();
        $job->id = 3;
        $printEvent = new PrintJobCreated($job);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $printEvent);
        $this->assertSame('print-jobs', $this->channelName($printEvent->broadcastOn()));
        $this->assertSame('print-job.created', $printEvent->broadcastAs());
    }

    public function test_global_pusher_script_binds_livewire_channels(): void
    {
        $script = file_get_contents(resource_path('views/sections/pusher-script.blade.php'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString("bindChannel('today-orders', 'today-orders.updated'", $script);
        $this->assertStringContainsString("bindChannel('today-reservations', 'today-reservations.created'", $script);
        $this->assertStringContainsString("bindChannel('active-waiter-requests', 'active-waiter-requests.created'", $script);
        $this->assertStringContainsString("bindChannel('orders', 'order.updated'", $script);
        $this->assertStringContainsString("bindChannel('orders', 'order.created'", $script);
        $this->assertStringContainsString("bindChannel('order-success', 'order-success.created'", $script);
        $this->assertStringContainsString("deliverToLivewire('customer-display', 'refreshCustomerDisplay'", $script);
        $this->assertStringContainsString("deliverToLivewire('customer-order-board', 'refreshBoard'", $script);
        $this->assertStringContainsString("deliverToLivewire('dashboard.today-orders', 'refreshOrders'", $script);
        $this->assertStringContainsString("channel.bind('kot.updated'", $script);
    }

    private function channelName(Channel|array $channels): string
    {
        if (is_array($channels)) {
            $channels = $channels[0];
        }

        return $channels->name;
    }
}
