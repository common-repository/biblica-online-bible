<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Util;

interface CacheInterface
{
    public function get(string $key, callable $callback);
    public function deleteKey(string $key): bool;
    public function invalidateTag(string $tag): bool;
    public function invalidateCache(): bool;
}