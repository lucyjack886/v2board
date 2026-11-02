<?php

namespace App\Utils;

class SubscribeServerRewrite
{
    private const HOST_PATTERN = '/^[A-Za-z0-9_\-\.:\[\]]+$/';

    private const LEVELS = [-3, -2, -1, 0, 1, 2, 3];

    /**
     * 按连接类型、原始 Host 和用户等级替换节点 host / port / SNI。
     * 加密订阅读取 host_rules，未加密订阅读取 host_rules_unencrypted。
     * 返回本次实际命中的目标 host（去重，供订阅日志使用）。
     *
     * @return string[]
     */
    public static function applyToServers(array &$servers, bool $encrypted, $userLevel, ?array $config = null): array
    {
        if ($config === null) {
            $config = self::loadConfig();
        }
        if ($servers === [] || $config === []) {
            return [];
        }

        $entries = self::hostEntries($config);
        $sniMap = is_array($config['sni_map'] ?? null) ? $config['sni_map'] : [];
        $ruleKey = $encrypted ? 'host_rules' : 'host_rules_unencrypted';
        $level = (int) $userLevel;
        $applied = [];

        foreach ($servers as &$server) {
            if (!is_array($server)) {
                continue;
            }
            $originHost = trim((string) ($server['host'] ?? ''));
            if ($originHost === '' || !isset($entries[$originHost])) {
                continue;
            }

            $rules = $entries[$originHost][$ruleKey] ?? [];
            if (!is_array($rules)) {
                continue;
            }

            $rule = self::matchRule($rules, $level);
            if ($rule === null) {
                continue;
            }

            $targetHost = trim((string) ($rule['host'] ?? ''));
            if ($targetHost === '' || !preg_match(self::HOST_PATTERN, $targetHost)) {
                continue;
            }

            $server['host'] = $targetHost;
            if (self::configuredPort($rule) !== null) {
                $server['port'] = self::configuredPort($rule);
                unset($server['mport']);
            }
            if (self::sniEnabled($rule['sni'] ?? false)) {
                $mappedSni = self::lookupSni($sniMap, $server['id'] ?? null);
                if ($mappedSni !== null) {
                    self::applySni($server, $mappedSni);
                }
            }
            $applied[$targetHost] = true;
        }
        unset($server);

        return array_keys($applied);
    }

    public static function loadConfig(): array
    {
        $config = config('v2board.server_rewrite', []);
        return is_array($config) ? $config : [];
    }

    /**
     * 后台表单会把布尔值和数字压成字符串，入库前还原成 JSON 类型。
     */
    public static function normalizeConfig(array $value): array
    {
        foreach ($value as $host => &$entry) {
            if ((string) $host === 'sni_map' || !is_array($entry)) {
                continue;
            }
            foreach (['host_rules', 'host_rules_unencrypted'] as $field) {
                if (!isset($entry[$field]) || !is_array($entry[$field])) {
                    continue;
                }
                foreach ($entry[$field] as &$rule) {
                    if (!is_array($rule)) {
                        continue;
                    }
                    if (isset($rule['host'])) {
                        $rule['host'] = trim((string) $rule['host']);
                    }
                    if (isset($rule['levels']) && is_array($rule['levels'])) {
                        foreach ($rule['levels'] as &$level) {
                            if (is_string($level) && preg_match('/^-?\d+$/', $level)) {
                                $level = (int) $level;
                            }
                        }
                        unset($level);
                    }
                    if (array_key_exists('port', $rule) && is_string($rule['port']) && preg_match('/^\d+$/', $rule['port'])) {
                        $rule['port'] = (int) $rule['port'];
                    }
                    if (array_key_exists('sni', $rule)) {
                        $rule['sni'] = self::normalizeBool($rule['sni']);
                    }
                }
                unset($rule);
            }
        }
        unset($entry);

        if (isset($value['sni_map']) && is_array($value['sni_map'])) {
            foreach ($value['sni_map'] as $nodeId => $sni) {
                if (is_string($sni)) {
                    $value['sni_map'][$nodeId] = trim($sni);
                }
            }
        }

        return $value;
    }

    public static function validateConfig($value, callable $fail): void
    {
        if ($value === null || $value === []) {
            return;
        }
        if (!is_array($value) || self::isList($value)) {
            $fail('订阅节点替换必须是 JSON 对象');
            return;
        }

        if (array_key_exists('sni_map', $value)) {
            if (!is_array($value['sni_map'])) {
                $fail('sni_map 必须是对象');
                return;
            }
            foreach ($value['sni_map'] as $nodeId => $sni) {
                if (!preg_match('/^\d+$/', (string) $nodeId)) {
                    $fail('sni_map 的键必须是节点 ID');
                    return;
                }
                if (!is_string($sni) || !preg_match(self::HOST_PATTERN, trim($sni))) {
                    $fail('sni_map 的值只能填写域名或 IP');
                    return;
                }
            }
        }

        foreach ($value as $originHost => $entry) {
            if ((string) $originHost === 'sni_map') {
                continue;
            }
            $originHost = trim((string) $originHost);
            if ($originHost === '' || !preg_match(self::HOST_PATTERN, $originHost)) {
                $fail('原始 Host 只能填写域名或 IP');
                return;
            }
            if (!is_array($entry) || self::isList($entry)) {
                $fail($originHost . ' 的配置必须是对象');
                return;
            }
            foreach ($entry as $field => $fieldValue) {
                if (!in_array($field, ['host_rules', 'host_rules_unencrypted'], true)) {
                    $fail($originHost . ' 包含未知字段 ' . $field);
                    return;
                }
                if (!is_array($fieldValue) || !self::isList($fieldValue)) {
                    $fail($originHost . ' 的 ' . $field . ' 必须是数组');
                    return;
                }
                $seenLevels = [];
                foreach ($fieldValue as $index => $rule) {
                    if (!self::validateRule($rule, $fail, $originHost, (string) $field, $index, $seenLevels)) {
                        return;
                    }
                }
            }
        }
    }

    private static function validateRule($rule, callable $fail, string $originHost, string $field, $index, array &$seenLevels): bool
    {
        $label = $originHost . ' 的 ' . $field . ' 第 ' . ((int) $index + 1) . ' 条';
        if (!is_array($rule) || self::isList($rule)) {
            $fail($label . '必须是对象');
            return false;
        }

        $host = trim((string) ($rule['host'] ?? ''));
        if ($host === '' || !preg_match(self::HOST_PATTERN, $host)) {
            $fail($label . '的 host 只能填写域名或 IP');
            return false;
        }

        $levels = $rule['levels'] ?? null;
        if (!is_array($levels) || !self::isList($levels) || $levels === []) {
            $fail($label . '的 levels 必须是用户等级数组');
            return false;
        }
        foreach ($levels as $level) {
            if (!is_int($level) || !in_array($level, self::LEVELS, true)) {
                $fail($label . '的 levels 只能包含 -3、-2、-1、0、1、2、3');
                return false;
            }
            if (isset($seenLevels[$level])) {
                $fail($originHost . ' 的 ' . $field . ' 中等级 ' . $level . ' 重复');
                return false;
            }
            $seenLevels[$level] = true;
        }

        if (!array_key_exists('sni', $rule) || !is_bool($rule['sni'])) {
            $fail($label . '的 sni 必须是 true 或 false');
            return false;
        }

        if (array_key_exists('port', $rule) && $rule['port'] !== null && $rule['port'] !== '') {
            if (!is_int($rule['port']) || $rule['port'] < 1 || $rule['port'] > 65535) {
                $fail($label . '的 port 必须是 1 到 65535 的整数');
                return false;
            }
        }

        return true;
    }

    private static function hostEntries(array $config): array
    {
        $entries = [];
        foreach ($config as $host => $entry) {
            if ((string) $host === 'sni_map' || !is_array($entry)) {
                continue;
            }
            $host = trim((string) $host);
            if ($host === '') {
                continue;
            }
            $entries[$host] = $entry;
        }
        return $entries;
    }

    private static function matchRule(array $rules, int $userLevel): ?array
    {
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $levels = $rule['levels'] ?? null;
            if (!is_array($levels)) {
                continue;
            }
            foreach ($levels as $level) {
                if ((is_int($level) || (is_string($level) && preg_match('/^-?\d+$/', $level))) && (int) $level === $userLevel) {
                    return $rule;
                }
            }
        }
        return null;
    }

    private static function configuredPort(array $rule): ?int
    {
        if (!array_key_exists('port', $rule) || $rule['port'] === null || $rule['port'] === '') {
            return null;
        }
        if (is_int($rule['port'])) {
            return ($rule['port'] >= 1 && $rule['port'] <= 65535) ? $rule['port'] : null;
        }
        if (is_string($rule['port']) && preg_match('/^\d+$/', $rule['port'])) {
            $port = (int) $rule['port'];
            return ($port >= 1 && $port <= 65535) ? $port : null;
        }
        return null;
    }

    private static function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function sniEnabled($value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    private static function lookupSni(array $sniMap, $nodeId): ?string
    {
        $id = (int) $nodeId;
        if ($id <= 0 || !array_key_exists($id, $sniMap)) {
            return null;
        }
        $sni = trim((string) $sniMap[$id]);
        if ($sni === '' || !preg_match(self::HOST_PATTERN, $sni)) {
            return null;
        }
        return $sni;
    }

    private static function applySni(array &$server, string $sni): void
    {
        $server['server_name'] = $sni;
        foreach (['tls_settings', 'tlsSettings'] as $key) {
            if (!isset($server[$key]) || !is_array($server[$key])) {
                continue;
            }
            $server[$key]['server_name'] = $sni;
            if (array_key_exists('serverName', $server[$key])) {
                $server[$key]['serverName'] = $sni;
            }
        }
    }

    private static function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }
        return array_keys($value) === range(0, count($value) - 1);
    }
}
