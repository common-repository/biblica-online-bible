<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\ApiDotBibleApi\Components;

use Biblica\Bible\ApiDotBibleApi\Utils\API;
use Biblica\Bible\Translations\Entities\Passage;
use Biblica\Bible\Translations\Entities\SearchHit;
use Biblica\Bible\Translations\Entities\SearchResult;
use Biblica\Bible\Translations\Entities\SortOrder;
use Biblica\Bible\Translations\Services\SearchServiceInterface;
use Biblica\Bible\Translations\Services\TranslationServiceInterface;
use Biblica\Util\LogUtilities;
use Exception;

class SearchService implements SearchServiceInterface
{
    use LogUtilities;

    public TranslationServiceInterface $translationService;

    public function __construct(TranslationServiceInterface $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @throws Exception
     */
    public function search(
        string $query,
        string $translationId,
        string $sortOrder,
        int $startPage,
        int $limit
    ): SearchResult {
        if (trim($query) === '') {
            return new SearchResult();
        }

        $start = ($startPage - 1) * $limit;

        if ($sortOrder === SortOrder::RELEVANCE) {
            $sortOrder = 'relevance';
        } elseif ($sortOrder === SortOrder::BOOK_ORDER) {
            $sortOrder = 'canonical';
        } else {
            $sortOrder = 'relevance';
        }

        $translation = $this->translationService->getTranslation($translationId);
        if ($translation === null) {
            return new SearchResult();
        }
        $bibleId = $translation->getId();

        $parameters = [];
        $parameters['query'] = $query;
        $parameters['limit'] = $limit;
        $parameters['offset'] = $start;
        $parameters['sort'] = $sortOrder;
        //$parameters['range'] = 'gen-rev'; // e.g. 'gen.1-gen.3'
        //$parameters['fuzziness'] = 'AUTO'; // 'AUTO', '1', '2', '3'

        // Query parameters in the url string will be overridden by the values
        // in the $parameters array. However the url string needs to include
        // them because it is used as the key value to cache search results.
        $url = sprintf(
            'bibles/%1$s/search?q=%2$s&limit=%3$s&offset=%4$s&sort=%5$s',
            urlencode($bibleId),
            urlencode($query),
            $limit,
            $start,
            urlencode($sortOrder)
        );

        $response = API::callAndCache($url, $parameters);

        if ($response === null) {
            return new SearchResult();
        }

        $from = 0;
        $to = 0;
        $total = 0;
        /** @var SearchHit[] $hits */
        $hits = [];

        if ($response->data !== null) {
            $data = $response->data;

            if ($data->verses !== null) {
                $from = $data->offset;
                $total = $data->total;
                $to = $from + $data->limit - 1;
                $to = ($to > $total - 1) ? $total - 1 : $to;

                foreach ($data->verses as $hit) {
                    $newSearchHit = new SearchHit();

                    $newPassage = new Passage();
                    $newPassage->translation = $translation;
                    $newPassage->content = $hit->text;
                    $newPassage->name = $hit->reference;
                    $newPassage->osis = mb_strtolower($hit->id);
                    $newSearchHit->passage = $newPassage;

                    $hits[] = $newSearchHit;
                }
            }
        }

        $newSearchResult = new SearchResult();
        $newSearchResult->from = $from;
        $newSearchResult->to = $to;
        $newSearchResult->total = $total;
        $newSearchResult->hits = $hits;

        return $newSearchResult;
    }
}
