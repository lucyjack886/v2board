<?php

namespace App\Payments;

class Epusdt {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'url' => [
                'label' => '支付网关地址',
                'description' => '例如：https://epusdt.com',
                'type' => 'input',
            ],
            'key' => [
                'label' => '接口认证 Token',
                'description' => '用于签名校验的密钥',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '140.238.156.77') {
            $order['return_url'] = str_replace(
                '140.238.156.77/#/',
                '140.238.156.77/xcvpn/#/',
                $order['return_url']
            );
        }

        $params = [
            'order_id'     => $order['trade_no'],
            'amount'       => round($order['total_amount'] / 100, 2), // 金额以元为单位，保留两位
            'notify_url'   => $order['notify_url'],
            'redirect_url' => $order['return_url']
        ];

        $params['signature'] = $this->sign($params, $this->config['key']);

        $url = rtrim($this->config['url'], '/') . '/api/v1/order/create-transaction';
        $response = $this->curlPost($url, $params);
        $result = json_decode($response, true);

        if (!is_array($result) || ($result['status_code'] ?? 0) !== 200) {
            throw new \Exception('epusdt 下单失败: ' . ($result['message'] ?? '未知错误'));
        }

        return [
            'type' => 1, // 1: URL跳转
            'data' => $result['data']['payment_url'] ?? ''
        ];
    }

    public function notify($params)
    {
        $signature = $params['signature'] ?? '';
        $signParams = [
            'order_id'             => $params['order_id'] ?? '',
            'amount'               => $params['amount'] ?? '',
            'actual_amount'        => $params['actual_amount'] ?? '',
            'block_transaction_id' => $params['block_transaction_id'] ?? '',
            'token'                => $params['token'] ?? '',
            'trade_id'             => $params['trade_id'] ?? '',
            'status'               => $params['status'] ?? '',
        ];

        $calculated = $this->sign($signParams, $this->config['key']);
        if ($calculated !== strtolower($signature)) {
            return false;
        }

        if ((int)($params['status'] ?? 0) !== 2) {
            return false; // 只处理支付成功状态
        }

        return [
            'trade_no'    => $params['order_id'],
            'callback_no' => $params['block_transaction_id']
        ];
    }

    private function sign(array $params, string $key): string
    {
        // 去除空值参数
        $filtered = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        // 按照 ASCII 字典序排序
        ksort($filtered);

        // 拼接成 key=value&key2=value2 格式
        $signStr = '';
        foreach ($filtered as $k => $v) {
            if ($k === 'signature') continue;
            $signStr .= ($signStr === '' ? '' : '&') . "$k=$v";
        }

        return strtolower(md5($signStr . $key));
    }

    private function curlPost($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}
