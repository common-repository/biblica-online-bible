<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class SearchResult
{
    public int $from = 0;
    public int $to = 0;
    public int $total = 0;
    /** @var SearchHit[] */
    public array $hits = [];
}
