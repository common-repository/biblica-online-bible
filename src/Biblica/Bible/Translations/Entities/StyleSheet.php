<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class StyleSheet
{
    /** @var bool */
    public $default;
    /** @var string */
    public $url;
    /** @var string */
    public $wrapperClasses;
    /** @var string */
    public $styles;
}
