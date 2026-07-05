<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SubscribeLogService;
use App\Utils\Helper;
use Closure;
use Illuminate\Support\Facades\Cache;

class ClientIpBound
{
    public function handle($request, Closure $next)
    {
        $submethod = (int)config('v2board.show_subscribe_method', 0);
        if ($submethod !== 1) {
            $this->abortSubscribe($request, 500, 'secure subscribe only supports otp mode');
        }

        $token = $request->input('token');
        if (empty($token)) {
            $this->abortSubscribe($request, 403, 'token is null');
        }

        $bind = Cache::get("sub_ip:{$token}");
        if (!$bind) {
            $this->abortSubscribe($request, 403, 'ticket or token expired');
        }

        $requestIp = Helper::getClientIp();
        if ($bind['ip'] !== $requestIp) {
            $this->abortSubscribe($request, 403, 'ip mismatch', [
                'user_id' => $bind['user_id'] ?? 0,
            ]);
        }

        $usertoken = Cache::pull("otpn_{$token}");
        if (!$usertoken) {
            $this->abortSubscribe($request, 403, 'token is error', [
                'user_id' => $bind['user_id'] ?? 0,
            ]);
        }
        Cache::forget("otp_{$usertoken}");

        $user = User::where('token', $usertoken)->first();
        if (!$user) {
            $this->abortSubscribe($request, 403, 'token is error', [
                'user_id' => $bind['user_id'] ?? 0,
            ]);
        }

        Cache::forget("sub_ip:{$token}");

        $request->merge([
            'user' => $user
        ]);

        return $next($request);
    }

    private function abortSubscribe($request, int $code, string $message, array $override = []): void
    {
        if (!empty($override['user_id']) && empty($override['email'])) {
            $user = User::find($override['user_id']);
            if ($user) {
                $override['email'] = $user->email;
            }
        }
        SubscribeLogService::record($request, false, $override);
        abort($code, $message);
    }
}
