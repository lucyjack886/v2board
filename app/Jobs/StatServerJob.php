<?php

namespace App\Jobs;

use App\Models\StatServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class StatServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    //protected $u;
    //protected $d;
    protected $data;
    protected $server;
    protected $protocol;
    protected $recordType;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data,array $server, $protocol, $recordType = 'd')
    {
        $this->onQueue('stat');
        //$this->u = $u;
        //$this->d = $d;
        $this->data = $data;
        $this->server = $server;
        $this->protocol = $protocol;
        $this->recordType = $recordType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $recordAt = strtotime(date('Y-m-d'));
        if ($this->recordType === 'm') {
            //
        }
        try {
            $u = 0;
            $d = 0;
            foreach (array_keys($this->data) as $userId) {
                $u += $this->data[$userId][0];
                $d += $this->data[$userId][1];
            }
            DB::statement(
                'INSERT INTO `v2_stat_server` (`server_id`,`server_type`,`u`,`d`,`record_type`,`record_at`,`created_at`,`updated_at`)
                 VALUES (?,?,?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE `u` = `u` + VALUES(`u`), `d` = `d` + VALUES(`d`), `updated_at` = VALUES(`updated_at`)',
                [$this->server['id'], $this->protocol, $u, $d, $this->recordType, $recordAt]
            );
        } catch (\Exception $e) {
            abort(500, '节点统计数据失败' . $e->getMessage());
        }
    }
}
