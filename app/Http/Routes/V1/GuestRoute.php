<?php
namespace App\Http\Routes\V1;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Http\Request;

class GuestRoute
{
    public function map(Registrar $router)
    {
        $router->group([
            'prefix' => 'guest'
        ], function ($router) {
            // Telegram
            $router->post('/telegram/webhook', 'V1\\Guest\\TelegramController@webhook');
            // Payment
            $router->match(['get', 'post'], '/payment/notify/{method}/{uuid}', 'V1\\Guest\\PaymentController@notify');
            $router->get('/payment/return/{method}/{uuid}', 'V1\\Guest\\PaymentController@callback');
            // Comm
            $router->get ('/comm/config', 'V1\\Guest\\CommController@config');
            // Plan - 公开查询计划
            $router->get('/plan/fetch', 'V1\\Guest\\PlanController@fetch');
            // App - 免登录获取客户端版本信息
            $router->get('/app/getVersion', function(Request $request) {
                return response([
                    'data' => [
                        'windows_version' => config('v2board.windows_version'),
                        'windows_download_url' => config('v2board.windows_download_url'),
                        'macos_version' => config('v2board.macos_version'),
                        'macos_download_url' => config('v2board.macos_download_url'),
                        'android_version' => config('v2board.android_version'),
                        'android_download_url' => config('v2board.android_download_url'),
                        'ios_version' => config('v2board.ios_version'),
                        'ios_download_url' => config('v2board.ios_download_url'),
                        'linux_version' => config('v2board.linux_version'),
                        'linux_download_url' => config('v2board.linux_download_url')
                    ]
                ]);
            });
        });
    }
}