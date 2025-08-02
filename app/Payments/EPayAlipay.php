<?php

namespace App\Payments;

class EPayAlipay {
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
            'special_hosts' => [
                'label' => '跳转xcvpn域名',
                'description' => '支持多IP或域名，逗号分隔；匹配成功会将 return_url 替换为 /xcvpn/',
                'type' => 'input',
            ],
            'key' => [
                'label' => 'KEY',
                'description' => '',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $specialHosts = $this->config['special_hosts'] ?? [];
        if (in_array($host, $specialHosts)) {
            $order['return_url'] = str_replace(
                $host . '/#/',
                $host . '/xcvpn/#/',
                $order['return_url']
            );
        }
        $params = [
            'money' => $order['total_amount'] / 100,
            'name' => $order['trade_no'],
            'notify_url' => $order['notify_url'],
            'return_url' => $order['return_url'],
            'out_trade_no' => $order['trade_no'],
            'type' => 'alipay',
            'pid' => $this->config['pid']
        ];
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
        if ($sign !== md5($str)) {
            return false;
        }
        return [
            'trade_no' => $params['out_trade_no'],
            'callback_no' => $params['trade_no']
        ];
    }
}
