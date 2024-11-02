<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\Bible\Translations\Entities\SearchResult;
use Biblica\WordPress\Plugin\Common\PageLink;

class BibleSearchDto
{
    public string $query;
    public string $sortOrder;
    public int $currentPage;
    public string $translationId;
    public array $searchHits;
    public string $searchPlaceholder;
    public bool $showPageLinks;
    public array $pageLinks;
    public ?PageLink $previousPageLink = null;
    public ?PageLink $nextPageLink = null;
    public ?SearchResult $searchResult = null;
    public string $resultsMessage;
    public int $totalResults;
    public string $title;
    public string $heading;
    public array $sortOrderDropDownItems;

    public ?string $disabledMessage = null;
}
