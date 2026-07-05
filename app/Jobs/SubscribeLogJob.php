<?php

namespace App\Jobs;

use App\Services\SubscribeLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscribeLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    public $tries = 2;
    public $timeout = 15;

    public function __construct(array $payload)
    {
        $this->onQueue('stat');
        $this->payload = $payload;
    }

    public function handle()
    {
        SubscribeLogService::persist($this->payload);
    }
}
