<?php

namespace App\Services;

use App\Jobs\SubscribeLogJob;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SubscribeLogService
{
    private const IP_API_FIELDS = 'status,message,query,country,regionName,city,district,isp,org,as,mobile,proxy,hosting';

    public static function shouldRecord($request): bool
    {
        $path = trim($request->path(), '/');
        $subscribePath = trim(config('v2board.subscribe_path', ''), '/');
        if ($subscribePath !== '' && $path === $subscribePath) {
            return true;
        }
        $paths = [
            'api/v1/client/subscribe',
            'api/v1/client/secureSubscribe',
        ];
        return in_array($path, $paths, true);
    }

    public static function record($request, array $override = []): void
    {
        try {
            if (!self::shouldRecord($request)) {
                return;
            }
            SubscribeLogJob::dispatch(self::buildPayload($request, $override));
        } catch (\Throwable $e) {
            Log::error('subscribe_log: ' . $e->getMessage());
        }
    }

    public static function buildPayload($request, array $override = []): array
    {
        $userId = $override['user_id'] ?? null;
        $email = $override['email'] ?? null;

        if ($userId === null || $email === null) {
            $user = $override['user'] ?? $request->input('user');
            if ($user instanceof User) {
                $userId = $userId ?? $user->id;
                $email = $email ?? $user->email;
            } elseif (is_array($user)) {
                $userId = $userId ?? ($user['id'] ?? null);
                $email = $email ?? ($user['email'] ?? null);
            }
        }

        if ($userId === null || $email === null) {
            $user = self::resolveUserFromToken($request->input('token'));
            if ($user) {
                $userId = $userId ?? $user->id;
                $email = $email ?? $user->email;
            }
        }

        return [
            'user_id' => (int)($userId ?? 0),
            'email' => (string)($email ?? ''),
            'ip' => Helper::getClientIp(),
            'user_agent' => $request->header('User-Agent', $_SERVER['HTTP_USER_AGENT'] ?? ''),
            'url' => $request->fullUrl(),
            'success' => 1,
        ];
    }

    public static function resolveUserFromToken(?string $token): ?User
    {
        if (empty($token)) {
            return null;
        }

        $user = User::where('token', $token)->first();
        if ($user) {
            return $user;
        }

        $submethod = (int)config('v2board.show_subscribe_method', 0);
        switch ($submethod) {
            case 1:
                $usertoken = Cache::get("otpn_{$token}");
                if ($usertoken) {
                    return User::where('token', $usertoken)->first();
                }
                break;
            case 2:
                $usertoken = Cache::get("totp_{$token}");
                if ($usertoken) {
                    return User::where('token', $usertoken)->first();
                }
                $timestep = (int)config('v2board.show_subscribe_expire', 5) * 60;
                $counter = floor(time() / $timestep);
                $counterBytes = pack('N*', 0) . pack('N*', $counter);
                $idhash = Helper::base64DecodeUrlSafe($token);
                if (strpos($idhash, ':') === false) {
                    return null;
                }
                $parts = explode(':', $idhash, 2);
                [$userid, $clienthash] = $parts;
                if (!$userid || !$clienthash) {
                    return null;
                }
                $user = User::where('id', $userid)->first();
                if (!$user) {
                    return null;
                }
                $hash = hash_hmac('sha1', $counterBytes, $user->token, false);
                if ($clienthash !== $hash) {
                    return null;
                }
                return $user;
        }

        return null;
    }

    public static function persist(array $payload): void
    {
        $ipInfo = self::lookupIp($payload['ip'] ?? '');
        SubscribeLog::create([
            'user_id' => $payload['user_id'],
            'email' => $payload['email'],
            'ip' => $payload['ip'],
            'country' => $ipInfo['country'] ?? null,
            'province' => $ipInfo['regionName'] ?? null,
            'city' => $ipInfo['city'] ?? null,
            'district' => $ipInfo['district'] ?? null,
            'isp' => $ipInfo['isp'] ?? null,
            'org' => $ipInfo['org'] ?? null,
            'as' => $ipInfo['as'] ?? null,
            'mobile' => isset($ipInfo['mobile']) ? (int)(bool)$ipInfo['mobile'] : 0,
            'hosting' => isset($ipInfo['hosting']) ? (int)(bool)$ipInfo['hosting'] : 0,
            'proxy' => isset($ipInfo['proxy']) ? (int)(bool)$ipInfo['proxy'] : 0,
            'user_agent' => $payload['user_agent'] ?? null,
            'url' => $payload['url'] ?? '',
            'success' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public static function isSuccessfulSubscribeResponse($response): bool
    {
        if ($response === null) {
            return false;
        }
        if (!method_exists($response, 'getStatusCode')) {
            return (string)$response !== '';
        }
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 400) {
            return false;
        }
        if (!method_exists($response, 'getContent')) {
            return true;
        }
        return $response->getContent() !== '';
    }

    private static function lookupIp(string $ip): array
    {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return [];
        }
        $cacheKey = 'subscribe_ip:' . $ip;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
        $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=' . self::IP_API_FIELDS . '&lang=zh-CN';
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return [];
        }
        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return [];
        }
        Cache::put($cacheKey, $data, 86400);
        return $data;
    }
}
