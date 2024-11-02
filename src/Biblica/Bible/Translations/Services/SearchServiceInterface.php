<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Services;

use Biblica\Bible\Translations\Entities\SearchResult;

interface SearchServiceInterface
{
    /**
     * Search for Bible content.
     *
     * @param string $query
     * @param string $translationId
     * @param string $sortOrder A string constant from the SortOrder class.
     * @param int $startPage
     * @param int $limit
     * @return SearchResult
     */
    public function search(
        string $query,
        string $translationId,
        string $sortOrder,
        int $startPage,
        int $limit
    ): SearchResult;
}
