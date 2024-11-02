<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Util;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class FileSystemCache extends Cache
{
    use LogUtilities;

    public function __construct(string $namespace, ?string $directory = null, ?int $ttl = null)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        if (!is_writeable($directory)) {
            $directory = null;
        }
        $sanitizedNamespace = $this->sanitizeString($namespace);
        $adapter = new TagAwareAdapter(new FileSystemAdapter($sanitizedNamespace, 0, $directory));

        parent::__construct($adapter, $ttl);
    }

    private function sanitizeString(string $string): string
    {
        $sanitizedString = str_replace('https://', '', $string);
        $sanitizedString = str_replace('http://', '', $sanitizedString);
        $sanitizedString = str_replace('/', '_', $sanitizedString);

        return $sanitizedString;
    }

    /**
     * @param string $key
     * @param callable $callback
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get(string $key, callable $callback)
    {
        return parent::get($this->sanitizeString($key), $callback);
    }

    public function deleteKey(string $key): bool
    {
        return parent::deleteKey($this->sanitizeString($key));
    }
}