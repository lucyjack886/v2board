<?php

namespace App\Protocols;

use RuntimeException;

class NextinEncrypted extends ClashMeta
{
    public $flag = 'nextinencrypted';

    // Configure UA substring matching here. Any hit will return encrypted Clash.Meta config.
    public const TARGET_UA_CONTAINS = [
        'BlueBird'
    ];

    // Shared password used by both server and client.
    public const ENCRYPTION_PASSWORD = 'xingchenPs20_3XbaB';

    // Configure how the semantic version is extracted from the UA string.
    // Supports nextin / vtx markers, and BlueBird-prefixed clients (e.g. "BlueBird/1.0.10", "BlueBird 1.0.10").
    public const VERSION_EXTRACT_REGEX = '/(?:nextin|vtx|bluebird)[^0-9]*([0-9]+(?:\.[0-9]+)+)/i';

    // Encrypt only when the parsed client version is greater than or equal to this version.
    public const MIN_CLIENT_VERSION = '1.0.9';

    // When true, matched UA with a version lower than MIN_CLIENT_VERSION will receive no subscription.
    public const BLOCK_LOWER_VERSION_SUBSCRIPTION = false;

    private $subscriptionUser;

    public function __construct($user, $servers)
    {
        parent::__construct($user, $servers);
        $this->subscriptionUser = $user;
    }

    public function handle()
    {
        $plainConfig = parent::handle();
        $userLevel = is_array($this->subscriptionUser)
            ? ($this->subscriptionUser['level'] ?? null)
            : ($this->subscriptionUser->level ?? null);
        $plainConfig = self::applyServerRewrite(
            $plainConfig,
            (array) config('v2board.encrypted_server_rewrite', []),
            $userLevel
        );
        header('content-type: text/plain; charset=utf-8');

        return self::encryptSubscriptionConfig($plainConfig, self::ENCRYPTION_PASSWORD);
    }

    /**
     * 根据后台配置对加密订阅 YAML 中的 server 字段做全量替换。
     * 仅影响 `server:` 字段值，不会误改 SNI、Host、节点名等同名字符串。
     *
     * 规则格式：源地址=>未知(0/null)=>低风险(1)=>白名单(2)=>恶意(-1)
     * 也支持 from + targets[4] 或旧版 from + to（所有等级共用同一目标）。
     */
    public static function applyServerRewrite($plainConfig, array $rules, $userLevel = null): string
    {
        $plainConfig = (string) $plainConfig;
        if ($plainConfig === '' || empty($rules)) {
            return $plainConfig;
        }

        foreach ($rules as $rule) {
            $normalized = self::normalizeRewriteRule($rule);
            if ($normalized === null) {
                continue;
            }

            $from = $normalized['from'];
            $to = self::resolveTargetHost($userLevel, $normalized['targets']);
            if ($from === '' || $to === '' || $from === $to) {
                continue;
            }

            $pattern = '/(^|[\s,\{\[])server:\s*(["\']?)' . preg_quote($from, '/') . '\2/m';
            $replaced = preg_replace($pattern, '$1server: ' . $to, $plainConfig);
            if (is_string($replaced)) {
                $plainConfig = $replaced;
            }
        }
        return $plainConfig;
    }

    /**
     * @return array{from: string, targets: string[]}|null
     */
    public static function normalizeRewriteRule($rule): ?array
    {
        if (is_string($rule)) {
            return self::normalizeRewriteRule(['rule' => $rule]);
        }
        if (!is_array($rule)) {
            return null;
        }

        if (isset($rule['rule']) && is_string($rule['rule'])) {
            $parts = array_values(array_filter(array_map('trim', explode('=>', $rule['rule'])), static function ($part) {
                return $part !== '';
            }));
            if (count($parts) < 2) {
                return null;
            }
            return [
                'from' => $parts[0],
                'targets' => array_slice($parts, 1),
            ];
        }

        $from = isset($rule['from']) ? trim((string) $rule['from']) : '';
        if ($from === '') {
            return null;
        }

        if (isset($rule['targets']) && is_array($rule['targets'])) {
            $targets = array_values(array_map(static function ($target) {
                return trim((string) $target);
            }, $rule['targets']));
            $targets = array_values(array_filter($targets, static function ($target) {
                return $target !== '';
            }));
            if (empty($targets)) {
                return null;
            }
            return [
                'from' => $from,
                'targets' => $targets,
            ];
        }

        if (isset($rule['to'])) {
            $to = trim((string) $rule['to']);
            if ($to === '') {
                return null;
            }
            return [
                'from' => $from,
                'targets' => [$to],
            ];
        }

        return null;
    }

    /**
     * targets 顺序：未知(0/null)、低风险(1)、白名单(2)、恶意(-1)
     */
    public static function resolveTargetHost($userLevel, array $targets): ?string
    {
        if (empty($targets)) {
            return null;
        }
        if (count($targets) === 1) {
            return $targets[0];
        }

        $index = match ((int) ($userLevel ?? 0)) {
            1 => 1,
            2 => 2,
            -1 => 3,
            default => 0,
        };

        if (!isset($targets[$index])) {
            return null;
        }

        $target = trim((string) $targets[$index]);
        return $target !== '' ? $target : null;
    }

    public static function shouldEncryptForUserAgent(?string $userAgent): bool
    {
        if (!self::matchesTargetUserAgent($userAgent)) {
            return false;
        }

        if (self::MIN_CLIENT_VERSION === '') {
            return true;
        }

        $version = self::extractVersionFromUserAgent($userAgent);
        if ($version === null) {
            return false;
        }

        return version_compare($version, self::MIN_CLIENT_VERSION, '>=');
    }

    public static function shouldBlockSubscriptionForUserAgent(?string $userAgent): bool
    {
        if (!self::BLOCK_LOWER_VERSION_SUBSCRIPTION) {
            return false;
        }

        if (!self::matchesTargetUserAgent($userAgent)) {
            return false;
        }

        if (self::MIN_CLIENT_VERSION === '') {
            return false;
        }

        $version = self::extractVersionFromUserAgent($userAgent);
        if ($version === null) {
            return true;
        }

        return version_compare($version, self::MIN_CLIENT_VERSION, '<');
    }

    public static function matchesTargetUserAgent(?string $userAgent): bool
    {
        $userAgent = strtolower((string) $userAgent);
        if ($userAgent === '') {
            return false;
        }

        foreach (self::TARGET_UA_CONTAINS as $needle) {
            $needle = strtolower(trim((string) $needle));
            if ($needle !== '' && strpos($userAgent, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function extractVersionFromUserAgent(?string $userAgent): ?string
    {
        $userAgent = (string) $userAgent;
        if ($userAgent === '') {
            return null;
        }

        if (preg_match(self::VERSION_EXTRACT_REGEX, $userAgent, $matches) !== 1) {
            return null;
        }

        return $matches[1] ?? null;
    }

    public static function encryptSubscriptionConfig(string $plainConfig, string $password): string
    {
        $key = hash('sha256', $password, true);
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plainConfig,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to encrypt Clash.Meta subscription config.');
        }

        // Output order: 12-byte nonce + ciphertext + 16-byte tag.
        return base64_encode($nonce . $ciphertext . $tag);
    }
}
