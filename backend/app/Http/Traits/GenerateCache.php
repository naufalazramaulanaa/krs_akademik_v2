<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Cache;

trait GenerateCache
{
    // Pengaturan Default TTL (Time To Live) dalam detik (Contoh: 30 menit = 1800 detik)
    protected int $defaultTtl = 1800; 

    protected function getCacheKey(string $prefix, array $params = []): string
    {
        return $prefix . '_' . md5(serialize($params));
    }

    protected function rememberCache(string $key, callable $callback, ?int $ttl = null)
    {
        $cacheTtl = $ttl ?? $this->defaultTtl;
        return Cache::remember($key, $cacheTtl, $callback);
    }

    protected function forgetCache(string $key): void
    {
        Cache::forget($key);
    }

    protected function flushCacheTag(string $tag): void
    {
        if (Cache::supportsTags()) {
            Cache::tags([$tag])->flush();
        } else {
            Cache::flush(); // Fallback jika driver cache tidak mendukung tags (misal: file)
        }
    }
}