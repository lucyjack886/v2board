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
        $submethod = (int)config('v2board.show_subscribe_method', 0);
        if ($submethod !== 1) {
            abort(500, 'secure subscribe only supports otp mode');
        }

        $token = $request->input('token');
        if (empty($token)) {
            abort(403, 'token is null');
        }

        $bind = Cache::get("sub_ip:{$token}");
        if (!$bind) {
            abort(403, 'ticket or token expired');
        }

        $requestIp = Helper::getClientIp();
        if ($bind['ip'] !== $requestIp) {
            abort(403, 'ip mismatch');
        }

        $usertoken = Cache::pull("otpn_{$token}");
        if (!$usertoken) {
            abort(403, 'token is error');
        }
        Cache::forget("otp_{$usertoken}");

        $user = User::where('token', $usertoken)->first();
        if (!$user) {
            abort(403, 'token is error');
        }

        Cache::forget("sub_ip:{$token}");

        $request->attributes->set('subscribe_usertoken', $usertoken);
        $request->merge([
            'user' => $user,
        ]);

        return $next($request);
    }
}
