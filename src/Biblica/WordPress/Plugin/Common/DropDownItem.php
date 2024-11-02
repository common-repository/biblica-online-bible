<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

/**
 * A single item in a select control
 *
 * @package Biblica\Web\OnlineBible
 */
class DropDownItem
{
    public string $value;
    public string $text;
    public bool $selected;

    public function __construct(string $text, string $value, bool $selected = false)
    {
        $this->text = $text;
        $this->value = $value;
        $this->selected = $selected;
    }
}
