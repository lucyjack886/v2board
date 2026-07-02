<?php

namespace App\Utils;

class SubscribeServerRewrite
{
    private const HOST_PATTERN = '/^[A-Za-z0-9_\-\.:\[\]]+$/';

    /**
     * 根据后台配置对 YAML 中的 server 字段做全量替换（加密订阅专用）。
     * 仅影响 `server:` 字段值，不会误改 SNI、Host、节点名等同名字符串。
     */
    public static function applyToYaml(string $yaml, array $rules, $userLevel = null): string
    {
        $yaml = (string) $yaml;
        if ($yaml === '' || empty($rules)) {
            return $yaml;
        }

        foreach ($rules as $rule) {
            $normalized = self::normalizeRewriteRule($rule);
            if ($normalized === null) {
                continue;
            }

            $from = $normalized['from'];
            $to = self::resolveTargetHost($userLevel, $normalized['targets']);
            if ($from === '' || $to === null || $to === '' || $from === $to) {
                continue;
            }

            $pattern = '/(^|[\s,\{\[])server:\s*(["\']?)' . preg_quote($from, '/') . '\2/m';
            $replaced = preg_replace($pattern, '$1server: ' . $to, $yaml);
            if (is_string($replaced)) {
                $yaml = $replaced;
            }
        }

        return $yaml;
    }

    /**
     * 在协议生成前替换节点 host（未加密订阅专用）。
     */
    public static function applyToServers(array &$servers, array $rules, $userLevel = null): void
    {
        if (empty($servers) || empty($rules)) {
            return;
        }

        foreach ($servers as &$server) {
            $host = isset($server['host']) ? trim((string) $server['host']) : '';
            if ($host === '') {
                continue;
            }

            foreach ($rules as $rule) {
                $normalized = self::normalizeRewriteRule($rule);
                if ($normalized === null || $normalized['from'] !== $host) {
                    continue;
                }

                $to = self::resolveTargetHost($userLevel, $normalized['targets']);
                if ($to !== null && $to !== '' && $to !== $host) {
                    $server['host'] = $to;
                }
                break;
            }
        }
        unset($server);
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
     * targets 顺序（5 段新规则）：
     * 未知(0)、低风险(1)、白名单(2)、高风险(-2)、恶意(-1)
     * 兼容旧 4 段规则：未知、低风险、白名单、恶意（无高风险档位）
     */
    public static function resolveTargetHost($userLevel, array $targets): ?string
    {
        if (empty($targets)) {
            return null;
        }
        if (count($targets) === 1) {
            return $targets[0];
        }

        $targetCount = count($targets);
        $level = (int) $userLevel;
        $index = match ($level) {
            1 => 1,
            2 => 2,
            -2 => $targetCount >= 5 ? 3 : null,
            -1 => $targetCount >= 5 ? 4 : 3,
            default => 0,
        };

        if ($index === null || !isset($targets[$index])) {
            return null;
        }

        $target = trim((string) $targets[$index]);
        return $target !== '' ? $target : null;
    }

    public static function validateRules(array $rules, callable $fail, string $label = '订阅节点替换'): void
    {
        foreach ($rules as $item) {
            if (is_string($item)) {
                $item = ['rule' => $item];
            }
            if (!is_array($item)) {
                $fail($label . '规则格式不正确');
                return;
            }

            if (isset($item['rule']) && is_string($item['rule']) && trim($item['rule']) === '') {
                continue;
            }

            $normalized = self::normalizeRewriteRule($item);
            if ($normalized === null) {
                if (isset($item['from'], $item['to']) && trim((string) $item['from']) === '' && trim((string) $item['to']) === '') {
                    continue;
                }
                $fail($label . '规则格式不正确，需为 源=>未知=>低风险=>白名单=>高风险=>恶意');
                return;
            }

            $from = $normalized['from'];
            $targets = $normalized['targets'];
            if ($from === '') {
                continue;
            }
            if (!preg_match(self::HOST_PATTERN, $from)) {
                $fail($label . '规则只能填写域名或IP，不能包含协议或路径');
                return;
            }
            foreach ($targets as $target) {
                if (!preg_match(self::HOST_PATTERN, $target)) {
                    $fail($label . '规则只能填写域名或IP，不能包含协议或路径');
                    return;
                }
            }
            if (count($targets) > 1 && !in_array(count($targets), [4, 5], true)) {
                $fail($label . '规则需包含 5 个分级目标地址（兼容旧版 4 个）');
                return;
            }
        }
    }
}
