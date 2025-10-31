<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderHandleJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $tradeNo;

    public $tries = 3;
    public $timeout = 5;
    public $uniqueFor = 120;

    public function uniqueId()
    {
        return (string) $this->tradeNo;
    }
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tradeNo)
    {
        $this->onQueue('order_handle');
        $this->tradeNo = $tradeNo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = Order::where('trade_no', $this->tradeNo)->first();

        if (!$order) return;

        $orderService = new OrderService($order);
        switch ($order->status) {
            case 0:
                if ($order->created_at <= (time() - 3600 * 2)) {
                    $orderService->cancel();
                }
                break;
            case 1:
                $orderService->open();
                break;
        }
    }
}
