<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Utils\Helper;
use Closure;
use Illuminate\Support\Facades\Cache;

class ClientIpBound
{
    public function handle($request, Closure $next)
    {
        $token = $request->input('token');
        if (empty($token)) {
            abort(403, 'token is null');
        }

        if ((int)config('v2board.secure_subscribe_ip_enable', 1)) {
            $bind = Cache::get("sub_ip:{$token}");
            if (!$bind) {
                abort(403, 'ticket or token expired');
            }

            $requestIp = Helper::getClientIp();
            if ($bind['ip'] !== $requestIp) {
                abort(403, 'ip mismatch');
            }
        }

        $userToken = $this->resolveUserToken($token);
        $user = User::where('token', $userToken)->first();
        if (!$user) {
            abort(403, 'token is error');
        }

        if ((int)config('v2board.secure_subscribe_ip_enable', 1)) {
            Cache::forget("sub_ip:{$token}");
        }

        $request->attributes->set('subscribe_usertoken', $userToken);
        $request->merge([
            'user' => $user,
        ]);

        return $next($request);
    }

    private function resolveUserToken(string $token): string
    {
        $submethod = (int)config('v2board.show_subscribe_method', 0);
        switch ($submethod) {
            case 1:
                $usertoken = Cache::pull("otpn_{$token}");
                if (!$usertoken) {
                    abort(403, 'token is error');
                }
                Cache::forget("otp_{$usertoken}");
                return $usertoken;
            case 2:
                $usertoken = Cache::get("totp_{$token}");
                if (!$usertoken) {
                    $timestep = (int)config('v2board.show_subscribe_expire', 5) * 60;
                    $counter = floor(time() / $timestep);
                    $counterBytes = pack('N*', 0) . pack('N*', $counter);
                    $idhash = Helper::base64DecodeUrlSafe($token);
                    if (strpos($idhash, ':') === false) {
                        abort(403, 'token is error');
                    }
                    $parts = explode(':', $idhash, 2);
                    [$userid, $clienthash] = $parts;
                    if (!$userid || !$clienthash) {
                        abort(403, 'token is error');
                    }
                    $user = User::where('id', $userid)->select('token')->first();
                    if (!$user) {
                        abort(403, 'token is error');
                    }
                    $usertoken = $user->token;
                    $hash = hash_hmac('sha1', $counterBytes, $usertoken, false);
                    if ($clienthash !== $hash) {
                        abort(403, 'token is error');
                    }
                    Cache::put("totp_{$token}", $usertoken, $timestep);
                }
                return $usertoken;
            default:
                $usertoken = Cache::get("securesub_{$token}");
                if (!$usertoken) {
                    abort(403, 'token is error');
                }
                return $usertoken;
        }
    }
}
