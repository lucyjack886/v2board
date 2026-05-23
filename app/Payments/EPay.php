<?php

namespace App\Payments;

class EPay {
    
    public function __construct($config)
    {
        if (isset($config['special_hosts']) && is_string($config['special_hosts'])) {
            $config['special_hosts'] = array_map('trim', explode(',', $config['special_hosts']));
        }
        $this->config = $config;
    }
    

    public function form()
    {
        return [
            'url' => [
                'label' => 'URL',
                'description' => '',
                'type' => 'input',
            ],
            'pid' => [
                'label' => 'PID',
                'description' => '',
                'type' => 'input',
            ],
            'hosts' => [
                'label' => '特殊跳转xcvpn域名',
                'description' => '支持多个 IP 或域名，用逗号或换行分隔；匹配成功会将 return_url 替换为 /xcvpn/',
                'type' => 'textarea',
            ],
            'key' => [
                'label' => 'KEY',
                'description' => '',
                'type' => 'input',
            ],
            'type' => [
                'label' => 'TYPE',
                'description' => '支付类型，如: alipay, wxpay, qqpay',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // 如果是 140.238.156.77 域名，则在路径中插入 /xcvpn
        if ($host === '140.238.156.77') {
            $order['return_url'] = str_replace(
                '140.238.156.77/#/',
                '140.238.156.77/xcvpn/#/',
                $order['return_url']
            );
        }
        $params = [
            'money' => $order['total_amount'] / 100,
            'name' => $order['trade_no'],
            'notify_url' => $order['notify_url'],
            'return_url' => $order['return_url'],
            'out_trade_no' => $order['trade_no'],
            'pid' => $this->config['pid']
        ];
        if (!empty($this->config['type'])) {
            $params['type'] = $this->config['type'];
        }
        ksort($params);
        reset($params);
        $str = stripslashes(urldecode(http_build_query($params))) . $this->config['key'];
        $params['sign'] = md5($str);
        $params['sign_type'] = 'MD5';
        return [
            'type' => 1, // 0:qrcode 1:url
            'data' => $this->config['url'] . '/submit.php?' . http_build_query($params)
        ];
    }

    public function notify($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['sign_type']);
        ksort($params);
        reset($params);
        $str = stripslashes(urldecode(http_build_query($params))) . $this->config['key'];
        $generateSignature = md5($str);
        if (!hash_equals($generateSignature, $sign)) {
            return false;
        }

        // 强制要求交易状态为成功，避免未支付/处理中状态被误入账
        $tradeStatus = $params['trade_status'] ?? '';
        if ($tradeStatus !== 'TRADE_SUCCESS') {
            return('fail');
        }

        return [
            'trade_no' => $params['out_trade_no'],
            'callback_no' => $params['trade_no']
        ];
    }
}
