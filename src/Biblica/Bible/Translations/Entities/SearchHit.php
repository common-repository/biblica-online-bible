<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Entities;

class SearchHit
{
    public int $number;
    public ?Passage $passage = null;
    public ?string $url = null;
}
