<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General; 
use App\Protocols\NextinEncrypted;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Services\ServerService;
use App\Services\SubscribeLogService;
use App\Services\UserService;
use App\Utils\Helper;
use App\Utils\HoneypotSubscribe;
use App\Utils\SubscribeServerRewrite;
use Illuminate\Http\Request;

class ClientController extends Controller
{

    public function subscribe(Request $request)
    {
        $response = null;
        try {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $flag = $request->input('flag')
                ?? $userAgent;
            $flag = strtolower($flag);
            $user = $request->input('user');
            // account not expired and is not banned.
            $userService = new UserService();
            if (!$userService->isAvailable($user)) {
                return;
            }
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
            $userLevel = (int) (is_array($user) ? ($user['level'] ?? 0) : ($user->level ?? 0));
            $shouldReturnEncryptedClashMeta = false;

            if ($flag) {
                $nextinEncrypted = new NextinEncrypted($user, $servers);
                $shouldBlockNextinSubscription =
                    NextinEncrypted::shouldBlockSubscriptionForUserAgent($userAgent);
                $shouldReturnEncryptedClashMeta =
                    NextinEncrypted::shouldEncryptForUserAgent($userAgent)
                    || strpos($flag, $nextinEncrypted->flag) !== false;

                if ($shouldBlockNextinSubscription) {
                    $response = response('', 403);
                    return $response;
                }
            }

            if (HoneypotSubscribe::shouldServe($userLevel)) {
                $honeypotYaml = HoneypotSubscribe::fetchYaml();
                if ($honeypotYaml !== null && $honeypotYaml !== '') {
                    $request->attributes->set('subscribe_rewrite_targets', 'honeypot');
                    if ($shouldReturnEncryptedClashMeta) {
                        header('content-type: text/plain; charset=utf-8');
                        $response = NextinEncrypted::encryptSubscriptionConfig(
                            $honeypotYaml,
                            NextinEncrypted::ENCRYPTION_PASSWORD
                        );
                        return $response;
                    }
                    header('content-type: text/plain; charset=utf-8');
                    $response = $honeypotYaml;
                    return $response;
                }
            }

            $rewriteRules = $shouldReturnEncryptedClashMeta
                ? (array) config('v2board.encrypted_server_rewrite', [])
                : (array) config('v2board.plain_server_rewrite', []);
            $request->attributes->set(
                'subscribe_rewrite_targets',
                implode(',', SubscribeServerRewrite::collectAppliedTargets($servers, $rewriteRules, $userLevel))
            );

            if (!$shouldReturnEncryptedClashMeta) {
                SubscribeServerRewrite::applyToServers(
                    $servers,
                    (array) config('v2board.plain_server_rewrite', []),
                    $userLevel
                );
            }

            if($flag) {
                if ($shouldReturnEncryptedClashMeta || strpos($flag, 'sing') === false) {
                    $this->setSubscribeInfoToServers($servers, $user);
                    $nextinEncrypted = new NextinEncrypted($user, $servers);
                }

                if (
                    $shouldReturnEncryptedClashMeta
                ) {
                    $response = $nextinEncrypted->handle();
                    return $response;
                }

                if (strpos($flag, 'sing') === false) {
                    foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Protocols\\' . basename($file, '.php');
                        $class = new $file($user, $servers);
                        if (strpos($flag, $class->flag) !== false) {
                            $response = $class->handle();
                            return $response;
                        }
                    }
                }
                if (strpos($flag, 'sing') !== false) {
                    $version = null;
                    if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)) {
                        $version = $matches[1];
                    }
                    if (!is_null($version) && version_compare($version, '1.12.0', '>=')) {
                        $class = new Singbox($user, $servers);
                    } else {
                        $class = new SingboxOld($user, $servers);
                    }
                    $response = $class->handle();
                    return $response;
                }
            }
            $class = new General($user, $servers);
            $response = $class->handle();
            return $response;
        } finally {
            if (SubscribeLogService::isSuccessfulSubscribeResponse($response)) {
                SubscribeLogService::record($request);
            }
        }
    }

    public function secureSubscribe(Request $request)
    {
        return $this->subscribe($request);
    }

    private function setSubscribeInfoToServers(&$servers, $user)
    {
        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : '长期有效';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        array_unshift($servers, array_merge($servers[0], [
            'name' => "套餐到期：{$expiredDate}",
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "距离下次重置剩余：{$resetDay} 天",
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "剩余流量：{$remainingTraffic}",
        ]));
    }
}
