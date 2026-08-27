<?php

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use Adapterman\Adapterman;
use Workerman\Worker;
use Illuminate\Support\Facades\Cache;

putenv('APP_RUNNING_IN_CONSOLE=false');
define('MAX_REQUEST', 20000);
define('isWEBMAN', true);

Adapterman::init();

$port = (int)($_ENV['WEBMAN_PORT'] ?? 6600);
$serverPort = $port + 10;

$onWorkerStart = static function () {
    require __DIR__ . '/start.php';
};

$onMessage = static function ($connection, $request) {
    static $request_count = 0;
    static $pid;
    if ($request_count == 1) {
        $pid = posix_getppid();
        Cache::forget("WEBMANPID");
        Cache::forever("WEBMANPID", $pid);
    }
    $connection->send(run());
    // 不再按请求数回收 worker：Workerman 的 Worker::stop() 会关闭监听套接字，
    // 但进程不退出、master 也不会补新进程，worker 会逐个失去 accept 能力，
    // 最终整个池只剩 master 持有 listen socket，请求全部积压在队列里。
    ++$request_count;
};

$http_worker = new Worker("http://127.0.0.1:{$port}");
$http_worker->count = 16;
$http_worker->name = 'AdapterMan';
$http_worker->onWorkerStart = $onWorkerStart;
$http_worker->onMessage = $onMessage;

$server_worker = new Worker("http://127.0.0.1:{$serverPort}");
$server_worker->count = 8;
$server_worker->name = 'AdapterMan-Server';
$server_worker->onWorkerStart = $onWorkerStart;
$server_worker->onMessage = $onMessage;

Worker::runAll();
