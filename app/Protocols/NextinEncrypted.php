<?php

namespace App\Protocols;

use App\Utils\SubscribeServerRewrite;
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
        $plainConfig = SubscribeServerRewrite::applyToYaml(
            $plainConfig,
            (array) config('v2board.encrypted_server_rewrite', []),
            $userLevel
        );
        header('content-type: text/plain; charset=utf-8');

        return self::encryptSubscriptionConfig($plainConfig, self::ENCRYPTION_PASSWORD);
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
