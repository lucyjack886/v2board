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
                // 必须显式给出 Content-Type。裸 return 会沿用 AdapterMan 的默认头
                // text/html（Http.php），而 nginx 对 text/html 响应会交给 HTML 过滤器，
                // 后者清掉 Content-Length 改用 chunked，却不会为零长度正文发终止块，
                // 下游只能一直等到 proxy_read_timeout 60 秒。
                return response('', 200)->header('Content-Type', 'text/plain; charset=utf-8');
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
                    return $this->forceNonHtmlContentType($response);
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
                        return $this->forceNonHtmlContentType($response);
                    }
                    header('content-type: text/plain; charset=utf-8');
                    $response = $honeypotYaml;
                    return $this->forceNonHtmlContentType($response);
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
                    return $this->forceNonHtmlContentType($response);
                }

                if (strpos($flag, 'sing') === false) {
                    foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Protocols\\' . basename($file, '.php');
                        $class = new $file($user, $servers);
                        if (strpos($flag, $class->flag) !== false) {
                            $response = $class->handle();
                            return $this->forceNonHtmlContentType($response);
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
                    return $this->forceNonHtmlContentType($response);
                }
            }
            $class = new General($user, $servers);
            $response = $class->handle();
            return $this->forceNonHtmlContentType($response);
        } finally {
            if (SubscribeLogService::isSuccessfulSubscribeResponse($response)) {
                SubscribeLogService::record($request);
            }
        }
    }

    /**
     * 订阅响应必须避开 text/html。协议类用 PHP 的 header() 设了小写 content-type，
     * 而 Laravel 又给字符串返回值套上默认的 Content-Type: text/html; charset=UTF-8；
     * AdapterMan 按原样大小写存头，于是两个都发了出去。nginx 取前一个 text/html 交给
     * HTML 过滤器，后者丢掉 Content-Length 改用分块却不发终止块，下游等满 60 秒。
     */
    private function forceNonHtmlContentType($response)
    {
        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            $type = (string) $response->headers->get('Content-Type', '');
            if ($type === '' || \str_starts_with(\strtolower($type), 'text/html')) {
                $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
            }

            return $response;
        }

        if (\is_string($response)) {
            return response($response, 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        return $response;
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
