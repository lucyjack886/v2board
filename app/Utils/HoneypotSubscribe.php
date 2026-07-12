<?php

namespace App\Utils;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HoneypotSubscribe
{
    private const CACHE_TTL = 300;

    public static function shouldServe(int $userLevel): bool
    {
        if ($userLevel !== -3) {
            return false;
        }
        if (!(int) config('v2board.honeypot_subscribe_enable', 0)) {
            return false;
        }
        $url = trim((string) config('v2board.honeypot_subscribe_url', ''));
        return $url !== '' && stripos($url, 'https://') === 0;
    }

    public static function fetchYaml(): ?string
    {
        $url = trim((string) config('v2board.honeypot_subscribe_url', ''));
        if ($url === '' || stripos($url, 'https://') !== 0) {
            return null;
        }

        $token = trim((string) config('v2board.honeypot_subscribe_token', ''));
        // Include token hash so rotating credentials invalidates cache.
        $cacheKey = 'honeypot_subscribe_yaml:' . md5($url . '|' . $token);
        try {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && trim($cached) !== '') {
                return $cached;
            }

            $headers = [
                'Accept' => 'text/plain, text/yaml, application/vnd.github.raw, */*',
                'User-Agent' => 'v2board-honeypot-subscribe',
            ];
            if ($token !== '') {
                // GitHub private raw / API: Bearer works for fine-grained & classic PATs.
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->get($url);

            if (!$response->successful()) {
                Log::warning('honeypot_subscribe: fetch failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'auth' => $token !== '' ? 'token' : 'none',
                ]);
                return null;
            }

            $body = trim((string) $response->body());
            if ($body === '') {
                return null;
            }

            Cache::put($cacheKey, $body, self::CACHE_TTL);
            return $body;
        } catch (\Throwable $e) {
            Log::warning('honeypot_subscribe: ' . $e->getMessage(), [
                'url' => $url,
            ]);
            return null;
        }
    }
}
