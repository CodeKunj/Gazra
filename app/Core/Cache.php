<?php

declare(strict_types=1);

namespace App\Core;

class Cache
{
    private static array $memory = [];
    private static ?string $cacheDir = null;

    private static function getCacheDir(): string
    {
        if (self::$cacheDir === null) {
            $dir = defined('CIELO_STORAGE') ? CIELO_STORAGE . '/cache' : dirname(__DIR__, 2) . '/storage/cache';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            self::$cacheDir = $dir;
        }
        return self::$cacheDir;
    }

    private static function getFilePath(string $key): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return self::getCacheDir() . '/' . $sanitized . '_' . substr(md5($key), 0, 8) . '.cache.json';
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, self::$memory)) {
            $mem = self::$memory[$key];
            if ($mem['expires_at'] === 0 || $mem['expires_at'] >= time()) {
                return $mem['data'];
            }
            unset(self::$memory[$key]);
        }

        $file = self::getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || !array_key_exists('expires_at', $payload) || !array_key_exists('data', $payload)) {
            @unlink($file);
            return $default;
        }

        if ($payload['expires_at'] !== 0 && $payload['expires_at'] < time()) {
            @unlink($file);
            return $default;
        }

        self::$memory[$key] = $payload;
        return $payload['data'];
    }

    public static function set(string $key, $value, int $ttlSeconds = 3600): bool
    {
        $expiresAt = ($ttlSeconds > 0) ? (time() + $ttlSeconds) : 0;
        $payload = [
            'key' => $key,
            'created_at' => time(),
            'expires_at' => $expiresAt,
            'data' => $value,
        ];

        self::$memory[$key] = $payload;

        $file = self::getFilePath($key);
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return @file_put_contents($file, $encoded, LOCK_EX) !== false;
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $val = self::get($key);
        if ($val !== null) {
            return $val;
        }

        $computed = $callback();
        self::set($key, $computed, $ttlSeconds);
        return $computed;
    }

    public static function forget(string $key): bool
    {
        unset(self::$memory[$key]);
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    public static function flush(): bool
    {
        self::$memory = [];
        $dir = self::getCacheDir();
        $files = glob($dir . '/*.cache.json');
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        return true;
    }
}
