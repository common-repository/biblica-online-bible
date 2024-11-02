<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Util;

use Exception;
use Pimple\Container;

class ContainerManager
{
    private static ?Container $container = null;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    /**
     * @throws Exception
     */
    public static function getContainer(): Container
    {
        if (self::$container === null) {
            throw new Exception('Container not set in ' . __CLASS__);
        }

        return self::$container;
    }
}