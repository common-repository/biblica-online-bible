<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Entities;

class Language
{
    public string $iso;
    public string $name;
    public string $nameLocal;
    public string $script;
    public string $direction;
    public bool $isRightToLeft;
}
