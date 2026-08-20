<?php

declare(strict_types=1);

namespace App\Provider;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Apcu\ApcuCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Definitions\Reference;
use Yiisoft\Yii\RateLimiter\Storage\SimpleCacheStorage;

return [
    // In-memory (APCu) instead of file I/O wherever the extension is
    // loaded — backs two real consumers: the FastRoute route-dispatch
    // cache (only active when config/environments/prod/params.php's
    // enableCache is true) and yiisoft/rate-limiter's SimpleCacheStorage
    // below (all environments — file-based counters hit lock contention
    // under concurrent requests). Guarded rather than assumed: calling
    // apcu_fetch() etc. without the extension loaded is a hard fatal
    // error in PHP, not a graceful no-op, so this falls back to FileCache
    // cleanly anywhere apcu isn't present.
    CacheInterface::class => extension_loaded('apcu') ? ApcuCache::class : FileCache::class,

    YiiCacheInterface::class => Cache::class,

    SimpleCacheStorage::class => [
        '__construct()' => [Reference::to(CacheInterface::class)],
    ],
];
