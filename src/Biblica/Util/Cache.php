<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Util;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class Cache implements CacheInterface
{
    public const TAG_ALL = 'CacheItems_All';
    private ?int $ttl;
    private ?TagAwareCacheInterface $cache = null;

    public function __construct(TagAwareCacheInterface $cache, ?int $ttl = null)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * @throws Exception
     */
    private function getCache(): TagAwareCacheInterface
    {
        if ($this->cache === null) {
            throw new Exception('Cache service not set in ' . __CLASS__);
        }

        return $this->cache;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function get(string $key, callable $callback)
    {
        $newCallback = function(ItemInterface $item = null) use ($callback) {
            if ($item !== null) {
                $item->tag(self::TAG_ALL);
                if ($this->ttl !== null) {
                    $item->expiresAfter($this->ttl);
                }
            }

            return $callback($item);
        };

        return $this->getCache()->get($key, $newCallback);
    }

    public function deleteKey(string $key): bool
    {
        try {
            return $this->getCache()->delete($key);
        } catch (Exception|InvalidArgumentException $exception) {
            return false;
        }
    }

    public function invalidateTag(string $tag): bool
    {
        try {
            return $this->getCache()->invalidateTags([$tag]);
        } catch (Exception|InvalidArgumentException $exception) {
            return false;
        }
    }

    public function invalidateCache(): bool
    {
        try {
            return $this->getCache()->invalidateTags([self::TAG_ALL]);
        } catch (Exception|InvalidArgumentException $exception) {
            return false;
        }
    }
}