<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Util;

class FileManager
{
    public static function removeDirectory($directory): bool
    {
        if (file_exists($directory) && is_dir($directory)) {
            $objects = scandir($directory);
            foreach ($objects as $object) {
                if ($object === "." || $object === "..") {
                    continue;
                }
                if (filetype($directory . "/" . $object) === "dir") {
                    if (self::removeDirectory($directory . "/" . $object) === false) {
                        return false;
                    }
                } else {
                    if (unlink($directory . "/" . $object) === false) {
                        return false;
                    }
                }
            }
            return rmdir($directory);
        }

        return true;
    }
}