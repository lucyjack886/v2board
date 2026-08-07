<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Console\Command;

class SendUnpaidOrderMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:unpaidOrderMail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '未支付/已取消订单催付邮件提醒';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $remindAfter = 5 * 60;
        $lookback = 10 * 60;
        $now = time();
        $expireSoonAt = $now + 14 * 86400;
        $mailService = new MailService();

        // 每分钟执行：只扫近 10 分钟内、且已满 5 分钟的订单即可
        $orders = Order::query()
            ->from('v2_order as o')
            ->join('v2_user as u', 'u.id', '=', 'o.user_id')
            ->whereIn('o.status', [0, 2])
            ->where('o.total_amount', '>', 0)
            ->where('o.created_at', '<=', $now - $remindAfter)
            ->where('o.created_at', '>', $now - $lookback)
            ->where(function ($query) use ($now, $expireSoonAt) {
                $query->whereNull('u.plan_id')
                    ->orWhere('u.expired_at', '<=', $now)
                    ->orWhere(function ($q) use ($now, $expireSoonAt) {
                        $q->whereNotNull('u.plan_id')
                            ->where('u.expired_at', '>', $now)
                            ->where('u.expired_at', '<=', $expireSoonAt);
                    });
            })
            ->select([
                'o.*',
                'u.email as user_email',
                'u.plan_id as user_plan_id',
                'u.expired_at as user_expired_at',
            ])
            ->orderBy('o.created_at', 'DESC')
            ->get()
            // 同一用户多笔只催一次，取最近一笔
            ->unique('user_id')
            ->values();

        if ($orders->isEmpty()) {
            return;
        }

        $paymentChannels = Payment::where('enable', 1)
            ->orderBy('sort', 'ASC')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
        $paymentChannelsText = $paymentChannels
            ? implode('、', $paymentChannels)
            : __('all available payment channels');

        foreach ($orders as $order) {
            if (!$order->user_email) {
                continue;
            }
            $user = new User([
                'id' => $order->user_id,
                'email' => $order->user_email,
                'plan_id' => $order->user_plan_id,
                'expired_at' => $order->user_expired_at,
            ]);
            $user->id = $order->user_id;
            $mailService->remindUnpaidOrder($order, $user, $paymentChannelsText);
        }
    }
}
