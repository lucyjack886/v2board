<?php

namespace App\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 神秘支付聚合支付插件（v2board 版）
 *
 * 安装方式：将本文件复制到 app/Payments/ShengmiPay.php，
 * 在后台「支付设置」新增支付方式，选择 ShengmiPay 并填写对应参数。
 */
class ShengmiPay
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 管理后台配置表单
     */
    public function form(): array
    {
        return [
            'mch_no' => [
                'label'       => '商户号',
                'description' => '神秘支付商户号，例如 M1774593764',
                'type'        => 'input',
            ],
            'mch_key' => [
                'label'       => '商户密钥',
                'description' => '在神秘支付商户后台查看，请勿泄露',
                'type'        => 'input',
            ],
            'product_id' => [
                'label'       => '产品编码',
                'description' => '在神秘支付商户后台「产品管理」中查看，例如 7001（支付宝）/ 7002（微信）',
                'type'        => 'input',
            ],
            'pay_api_url' => [
                'label'       => '支付 API 地址',
                'description' => '不含末尾斜杠，例如 https://shenmi-pay-api.yzzf77.com',
                'type'        => 'input',
            ],
            'cache_ttl' => [
                'label'       => '支付链接缓存时长（分钟）',
                'description' => '同一订单缓存支付链接的时长，范围 1–60，默认 25。缓存期内重复发起支付直接返回已有链接，不再向通道创单',
                'type'        => 'input',
            ],
            'display_mode' => [
                'label'       => '支付展示方式',
                'description' => '填 redirect（直接跳转，适合支付宝/USDT，默认）或 qrcode（页面内嵌二维码，适合微信 native 支付，需同时填写下方站点地址）',
                'type'        => 'input',
            ],
            'site_url' => [
                'label'       => '站点地址（二维码模式必填）',
                'description' => '用户浏览器可访问的站点根域名，例如 https://example.com（不含末尾斜杠）。二维码页面 qr.html 需放在该域名根目录下',
                'type'        => 'input',
            ],
        ];
    }

    /**
     * 发起支付
     */
    public function pay(array $order): array
    {
        $mchNo       = (string) ($this->config['mch_no']      ?? '');
        $mchKey      = (string) ($this->config['mch_key']     ?? '');
        $productId   = (string) ($this->config['product_id']  ?? '');
        $baseUrl     = rtrim((string) ($this->config['pay_api_url'] ?? 'https://shenmi-pay-api.yzzf77.com'), '/');
        $cacheTtlSec = max(60, min(3600, (int) (($this->config['cache_ttl'] ?? 25) ?: 25) * 60));

        if (empty($mchNo) || empty($mchKey) || empty($productId) || empty($baseUrl)) {
            throw new \Exception('神秘支付配置不完整，请检查商户号、密钥、产品编码和 API 地址');
        }

        // notify_url 含本支付实例的唯一 UUID，用其哈希区分不同支付方式（支付宝/微信/USDT），
        // 避免多实例使用相同 productId 时缓存互相污染。
        $methodHash = md5($order['notify_url']);
        $cacheKey   = 'shengmi_pay_url_'     . $methodHash . '_' . $order['trade_no'];
        $createdKey = 'shengmi_pay_created_' . $methodHash . '_' . $order['trade_no'];

        // ── 阶段1：缓存命中，直接返回 ────────────────────────────────────────────
        if ($cachedUrl = Cache::get($cacheKey)) {
            return $this->buildPayResponse($cachedUrl);
        }

        // ── 阶段2：曾创单成功但缓存过期，通过查单接口取回支付链接 ─────────────────
        if (Cache::has($createdKey)) {
            $existingUrl = $this->queryPayUrl($order['trade_no'], $mchNo, $mchKey, $baseUrl);
            if ($existingUrl) {
                Cache::put($cacheKey, $existingUrl, $cacheTtlSec);
                return $this->buildPayResponse($existingUrl);
            }
        }

        // ── 阶段3：首次创单 ───────────────────────────────────────────────────────
        $reqTime    = (int) round(microtime(true) * 1000);
        $mchOrderNo = $order['trade_no'];

        $params = [
            'mchNo'      => $mchNo,
            'mchOrderNo' => $mchOrderNo,
            'productId'  => $productId,
            'amount'     => (int) $order['total_amount'],
            'clientIp'   => request()->ip() ?: '127.0.0.1',
            'notifyUrl'  => $order['notify_url'],
            'reqTime'    => $reqTime,
            'extParam'   => $order['trade_no'],
        ];

        if (!empty($order['return_url'])) {
            $params['returnUrl'] = $order['return_url'];
        }

        $params['sign'] = $this->generateSign($params, $mchKey);

        try {
            $response = Http::timeout(15)->post($baseUrl . '/api/pay/unifiedOrder', $params);
        } catch (ConnectionException $e) {
            Log::error('ShengmiPay 连接失败', [
                'url'   => $baseUrl . '/api/pay/unifiedOrder',
                'error' => $e->getMessage(),
                'order' => $order['trade_no'],
            ]);
            throw new \Exception('神秘支付网关连接失败，请检查 API 地址或稍后重试');
        }

        if (!$response->successful()) {
            throw new \Exception('神秘支付请求失败，HTTP ' . $response->status());
        }

        $result = $response->json();

        if (!is_array($result) || ($result['code'] ?? -1) !== 0) {
            $msg = is_array($result) ? ($result['msg'] ?? '未知错误') : '响应解析失败';

            // 并发场景：单号已存在，走查单兜底
            if (str_contains($msg, '已存在')) {
                $existingUrl = $this->queryPayUrl($mchOrderNo, $mchNo, $mchKey, $baseUrl);
                if ($existingUrl) {
                    Cache::put($cacheKey, $existingUrl, $cacheTtlSec);
                    Cache::put($createdKey, 1, 86400);
                    return $this->buildPayResponse($existingUrl);
                }
            }

            Log::error('ShengmiPay 下单失败', [
                'response' => $result,
                'trade_no' => $order['trade_no'],
            ]);
            throw new \Exception('神秘支付下单失败: ' . $msg);
        }

        $data       = $result['data'] ?? [];
        $orderState = (int) ($data['orderState'] ?? -1);

        if ($orderState !== 1) {
            throw new \Exception('神秘支付出码失败，状态码: ' . $orderState);
        }

        if (empty($data['payData'])) {
            throw new \Exception('神秘支付未返回支付链接');
        }

        Cache::put($cacheKey, $data['payData'], $cacheTtlSec);
        Cache::put($createdKey, 1, 86400);

        Log::info('ShengmiPay 创单成功', [
            'trade_no'   => $order['trade_no'],
            'product_id' => $productId,
        ]);

        return $this->buildPayResponse($data['payData']);
    }

    /**
     * 验证支付回调
     */
    public function notify(array $params): array|false
    {
        $mchKey = (string) ($this->config['mch_key'] ?? '');

        $receivedSign = strtoupper((string) ($params['sign'] ?? ''));
        unset($params['sign']);

        $expectedSign = $this->generateSign($params, $mchKey);

        if (!hash_equals($expectedSign, $receivedSign)) {
            Log::warning('ShengmiPay 回调签名校验失败', [
                'expected' => $expectedSign,
                'received' => $receivedSign,
            ]);
            return false;
        }

        // state: 2 = 支付成功，5 = 测试冲正（均视为成功）
        $state = (int) ($params['state'] ?? 0);
        if ($state !== 2 && $state !== 5) {
            return false;
        }

        // extParam 存储原始 v2board trade_no，优先取；兼容旧格式直接用 mchOrderNo
        $tradeNo = !empty($params['extParam'])
            ? (string) $params['extParam']
            : (string) ($params['mchOrderNo'] ?? '');

        return [
            'trade_no'    => $tradeNo,
            'callback_no' => (string) ($params['payOrderId'] ?? ''),
        ];
    }

    /**
     * 根据 display_mode 配置决定返回跳转 URL 还是二维码页面 URL
     */
    private function buildPayResponse(string $payUrl): array
    {
        if (($this->config['display_mode'] ?? 'redirect') === 'qrcode') {
            $siteUrl = rtrim((string) ($this->config['site_url'] ?? ''), '/');
            if ($siteUrl !== '') {
                return ['type' => 1, 'data' => $siteUrl . '/qr.html?data=' . urlencode($payUrl)];
            }
        }
        return ['type' => 1, 'data' => $payUrl];
    }

    /**
     * 通过查单 API 获取已有订单的支付链接
     */
    private function queryPayUrl(string $mchOrderNo, string $mchNo, string $mchKey, string $baseUrl): ?string
    {
        $reqTime = (int) round(microtime(true) * 1000);
        $params  = [
            'mchNo'      => $mchNo,
            'mchOrderNo' => $mchOrderNo,
            'reqTime'    => $reqTime,
        ];
        $params['sign'] = $this->generateSign($params, $mchKey);

        try {
            $response = Http::timeout(15)->post($baseUrl . '/api/pay/query', $params);
            if (!$response->successful()) {
                return null;
            }
            $result = $response->json();
            if (!is_array($result) || ($result['code'] ?? -1) !== 0) {
                return null;
            }
            return ($result['data'] ?? [])['payData'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('ShengmiPay 查单失败', [
                'mchOrderNo' => $mchOrderNo,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 签名算法：过滤空值 → ASCII 升序排列 → key=val&...&key=密钥 → MD5 大写
     */
    private function generateSign(array $params, string $secretKey): string
    {
        $filtered = array_filter($params, static fn($v) => $v !== null && (string) $v !== '');
        ksort($filtered);
        $pairs = [];
        foreach ($filtered as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        return strtoupper(md5(implode('&', $pairs) . '&key=' . $secretKey));
    }
}
