<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Utils\CacheKey;
use Illuminate\Support\Facades\Cache;

class MailService
{
    public function remindTraffic (User $user)
    {
        if (!$user->remind_traffic) return;
        if (!$this->remindTrafficIsWarnValue($user->u, $user->d, $user->transfer_enable)) return;
        $flag = CacheKey::get('LAST_SEND_EMAIL_REMIND_TRAFFIC', $user->id);
        if (Cache::get($flag)) return;
        if (!Cache::put($flag, 1, 24 * 3600)) return;
        SendEmailJob::dispatch([
            'email' => $user->email,
            'subject' => __('The traffic usage in :app_name has reached 95%', [
                'app_name' => config('v2board.app_name', 'V2board')
            ]),
            'template_name' => 'remindTraffic',
            'template_value' => [
                'name' => config('v2board.app_name', 'V2Board'),
                'url' => config('v2board.app_url')
            ]
        ]);
    }

    public function remindExpire(User $user)
    {
        if (!($user->expired_at !== NULL && ($user->expired_at - 86400) < time() && $user->expired_at > time())) return;
        SendEmailJob::dispatch([
            'email' => $user->email,
            'subject' => __('The service in :app_name is about to expire', [
               'app_name' =>  config('v2board.app_name', 'V2board')
            ]),
            'template_name' => 'remindExpire',
            'template_value' => [
                'name' => config('v2board.app_name', 'V2Board'),
                'url' => config('v2board.app_url')
            ]
        ]);
    }

    public function remindUnpaidOrder(Order $order, ?User $user = null, ?string $paymentChannels = null)
    {
        if (!in_array((int)$order->status, [0, 2], true)) return;
        if ((int)$order->total_amount <= 0) return;

        if (!$user) {
            $user = User::find($order->user_id);
        }
        if (!$user || !$user->email) return;

        // 按用户去重：同一用户多笔订单只催一次
        $flag = CacheKey::get('LAST_SEND_EMAIL_REMIND_UNPAID', $user->id);
        if (Cache::get($flag)) return;

        // 仅提醒：无有效套餐，或有效套餐将在 14 天内到期
        if (!$this->shouldRemindUnpaidByPlan($user)) return;

        $appUrl = rtrim(config('v2board.app_url'), '/');
        if ($paymentChannels === null) {
            $paymentNames = Payment::where('enable', 1)
                ->orderBy('sort', 'ASC')
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
            $paymentChannels = $paymentNames
                ? implode('、', $paymentNames)
                : __('all available payment channels');
        }

        try {
            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => __('Your order in :app_name is still unpaid', [
                    'app_name' => config('v2board.app_name', 'V2Board')
                ]),
                'template_name' => 'remindUnpaid',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => $appUrl,
                    'order_url' => $appUrl . '/#/order',
                    'trade_no' => $order->trade_no,
                    'amount' => number_format($order->total_amount / 100, 2),
                    'payment_channels' => $paymentChannels,
                ],
                'unpaid_order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            return;
        }

        Cache::put($flag, 1, 3 * 3600);
    }

    private function shouldRemindUnpaidByPlan(User $user): bool
    {
        // 无有效套餐
        if ($user->plan_id === null) return true;
        if ($user->expired_at === null) return false; // 终身有效，不催
        if ((int)$user->expired_at <= time()) return true;

        // 有效套餐 14 天内到期
        return (int)$user->expired_at <= time() + 14 * 86400;
    }

    private function remindTrafficIsWarnValue($u, $d, $transfer_enable)
    {
        $ud = $u + $d;
        if (!$ud) return false;
        if (!$transfer_enable) return false;
        $percentage = ($ud / $transfer_enable) * 100;
        if ($percentage < 95) return false;
        if ($percentage >= 100) return false;
        return true;
    }
}
