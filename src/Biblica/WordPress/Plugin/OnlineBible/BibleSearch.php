<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\WordPress\Plugin\OnlineBible\Exception\OnlineBibleException;
use Exception;
use Biblica\Bible\Translations\Entities\PassageFragments;
use Biblica\Bible\Translations\Entities\SearchResult;
use Biblica\Bible\Translations\Entities\SortOrder;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Util\LogUtilities;
use Biblica\Util\TwigManager;
use Biblica\WordPress\Plugin\Common\BibleApiPage;
use Biblica\WordPress\Plugin\Common\DropDownItem;
use Biblica\WordPress\Plugin\Common\HttpParameters;
use Biblica\WordPress\Plugin\Common\PageLink;
use Biblica\WordPress\Plugin\Common\SeoPageInterface;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;

class BibleSearch extends BibleApiPage implements SeoPageInterface
{
    use LogUtilities;

    private ?Translation $searchTranslation = null;
    private ?SearchResult $searchResult = null;
    private int $defaultResultsPerPage = 20;
    private int $maxPageLinks = 10;

    /**
     * @param WordPressPlugin $plugin
     * @param bool $render
     */
    public function __construct(WordPressPlugin $plugin, bool $render = false)
    {
        parent::__construct($plugin);

        $this->strings = $this->strings + [
            '/bible-search/not-available' => __('The Bible search page is not available right now. Please try again later.', 'biblica-online-bible'),
            '/biblica/templates/rtb/search/noResults' => __('No results were found. Please try a different search.', 'biblica-online-bible'),
            '/biblica/templates/rtb/search/queryPlaceholder' => __('Search the %s Bible', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearch/label' => __('Search', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearch/placeHolder' => __('Search the Bible', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearch/imageAltText' => __('Search', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearch/site' => __('Site', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearch/store' => __('Store', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearch/bible' => __('Bible', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearchLabel' => __('Search', 'biblica-online-bible'),
            '/biblica/templates/search/quickSearchPlaceHolder' => __('Search within list', 'biblica-online-bible'),
            '/biblica/templates/search/showAllLabel' => __('Show All', 'biblica-online-bible'),
            '/biblica/templates/search/moreLabel' => __('More Results', 'biblica-online-bible'),
            '/biblica/templates/search/moreProductsLabel' => __('More Products Results', 'biblica-online-bible'),
            '/biblica/templates/search/sortLabel' => __('Sort:', 'biblica-online-bible'),
            '/biblica/templates/search/sidebarHeading' => __('Filter Results', 'biblica-online-bible'),
            '/biblica/templates/search/topHeadingFormat' => __('Top {0} Results', 'biblica-online-bible'),
            '/biblica/templates/search/allHeadingFormat' => __('All {0} {1} Results', 'biblica-online-bible'),
            '/biblica/templates/search/previousPageLabel' => __('Previous page', 'biblica-online-bible'),
            '/biblica/templates/search/nextPageLabel' => __('Next page', 'biblica-online-bible'),
            '/biblica/templates/search/currentPageLabel' => __('current', 'biblica-online-bible'),
            '/bible/bible-search/results/meta-description' => __('Search the Bible', 'biblica-online-bible'),
            '/bible/bible-search/results/preposition' => __('hits on', 'biblica-online-bible'),
            '/bible/bible-search/sort-order/relevance' => __('Relevance', 'biblica-online-bible'),
            '/bible/bible-search/sort-order/book' => __('Book Order', 'biblica-online-bible'),
            '/search/disabled/unverified-bible-api-key' => __('Bible search is disabled. In order to use the Bible search you must enter a valid key in the Bible API Key field in settings.', 'biblica-online-bible'),
            '/search/disabled/bible-reader-page-id' => __('Bible search is disabled. In order to use the Bible search you must set the Bible Reader Page field in settings.', 'biblica-online-bible'),
            '/search/disabled/unavailable' => __('Bible search is not available right now. Please try again later.', 'biblica-online-bible'),
            '/search/results/no-query' => __('Please enter the text you would like to search for.', 'biblica-online-bible'),
        ];

        if ($render) {
            echo $this->render();
        }
    }

    public function render(): string
    {
        try {
            $data = $this->getTemplateData();

            $twigContext = [
                'data' => $data,
                'strings' => $this->getStrings(),
            ];

            $twigTemplate = 'bible-search.twig';
            $templateLocation = $this->getPlugin()->getPluginPath() . 'templates';
            $twig = TwigManager::createEnvironment($templateLocation);

            $html = $twig->render($twigTemplate, $twigContext);
        } catch (Exception $e) {
            $this->logException($e);
            $html = '<div class="disabled-message">' . $this->getString('/search/disabled/unavailable') . '</div>';
        }

        return $html;
    }

    public function getTemplateData(): BibleSearchDto
    {
        $data = new BibleSearchDto();

        $data->disabledMessage = $this->getDisabledMessage();
        if ($data->disabledMessage !== null) {
            return $data;
        }

        $data->heading = $this->getHeading();
        $data->searchResult = $this->getSearchResult();
        $data->resultsMessage = $this->getResultsMessage();
        $data->totalResults = $this->getTotalResults();
        $data->query = $this->getQuery();
        $data->sortOrder = $this->getSortOrder();
        $data->currentPage = $this->getCurrentPage();
        $data->translationId = $this->getSearchTranslation()->getId();
        $data->searchHits = $this->getSearchHits();
        $data->sortOrderDropDownItems = $this->getSortOrderDropDownItems();
        $data->showPageLinks = $this->showPageLinks();
        $data->pageLinks = $this->getPageLinks();
        $data->previousPageLink = $this->getPreviousPageLink();
        $data->nextPageLink = $this->getNextPageLink();
        $data->searchPlaceholder = $this->getSearchPlaceholder();

        return $data;
    }

    /**
     * @throws Exception
     */
    public function getDisabledMessage(): ?string
    {
        $message = null;

        $options = $this->getPlugin()->getOptions('general');
        $bibleApiKey = $options->getOption('bibleApiKey');
        $verifiedBibleApiKey = $options->getOption('verifiedBibleApiKey');
        $bibleReaderPageId = $options->getOption('bibleReaderPageId');
        if ($bibleApiKey !== $verifiedBibleApiKey) {
            $message = $this->getString('/search/disabled/unverified-bible-api-key');
        } elseif ($bibleReaderPageId === 0) {
            $message = $this->getString('/search/disabled/bible-reader-page-id');
        }

        if ($this->getSearchTranslation() === null) {
            if (!$this->redirectToDefault()) {
                $message = $this->getString('/search/disabled/unavailable');
            }
        }

        return $message;
    }

    /**
     * @throws Exception
     */
    protected function redirectToDefault(): bool
    {
        if (is_admin()) {
            return false;
        }

        $defaultTranslation = $this->getDefaultTranslation();
        if ($defaultTranslation === null) {
            // Cannot redirect to the default translation since it is not available
            return false;
        }

        $defaultTranslationId = $defaultTranslation->getId();
        $currentTranslationId = $this->getTranslationId();

        if ($defaultTranslationId === $currentTranslationId) {
            // Avoid a redirect loop
            return false;
        }

        $url = $this->getSearchUrl($this->getQuery(), $defaultTranslationId);
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Location: ' . $url, true, 302);
        exit;
    }

    public function getSearchTranslation(): ?Translation
    {
        if ($this->searchTranslation === null && $this->getTranslationId() === null) {
            $this->searchTranslation = $this->getDefaultTranslation();
        } elseif ($this->searchTranslation === null) {
            $this->searchTranslation = $this->getTranslation();
        }

        return $this->searchTranslation;
    }

    public function getQuery(): string
    {
        $searchQuery = $this->getVar(HttpParameters::$query, 100) ?? '';

        return sanitize_text_field($searchQuery);
    }

    public function getSortOrder(): string
    {
        $defaultOrder = SortOrder::RELEVANCE;
        $sortOrder = $this->getVar(HttpParameters::$sortBy, 9) ?? $defaultOrder;
        if ($sortOrder !== SortOrder::RELEVANCE && $sortOrder !== SortOrder::BOOK_ORDER) {
            $sortOrder = $defaultOrder;
        }

        return $sortOrder;
    }

    public function getCurrentPage(): int
    {
        $pageString = $this->getVar(HttpParameters::$page, 3) ?? '1';
        if (!is_numeric($pageString)) {
            $currentResultPage = 1;
        } else {
            $currentResultPage = intval($pageString);
        }

        if ($currentResultPage < 1) {
            $currentResultPage = 1;
        }

        return $currentResultPage;
    }

    public function getLastPage(): int
    {
        return (int)ceil($this->getTotalResults() / $this->getResultsPerPage());
    }

    public function getFirstPageLinkNumber(): int
    {
        $maxPageLinks = min($this->maxPageLinks, $this->getLastPage());

        $firstPageLink = $this->getCurrentPage() - intdiv($maxPageLinks, 2);
        return max($firstPageLink, 1);
    }

    public function getLastPageLinkNumber(): int
    {
        $lastPageLink = $this->getFirstPageLinkNumber() + $this->maxPageLinks - 1;
        return $lastPageLink > $this->getLastPage() ? $this->getLastPage() : $lastPageLink;
    }

    public function getPreviousPageLink(): ?PageLink
    {
        $previousPage = $this->getCurrentPage() - 1;
        if ($previousPage < $this->getFirstPageLinkNumber()) {
            return null;
        }

        $previousPageLink = new PageLink();
        $previousPageLink->text = '';
        $previousPageLink->url = $this->getPageLinkUrl($previousPage);

        return $previousPageLink;
    }

    public function getNextPageLink(): ?PageLink
    {
        $nextPage = $this->getCurrentPage() + 1;
        if ($nextPage > $this->getLastPageLinkNumber()) {
            return null;
        }

        $nextPageLink = new PageLink();
        $nextPageLink->text = '';
        $nextPageLink->url = $this->getPageLinkUrl($nextPage);

        return $nextPageLink;
    }

    public function getPageLinks(): array
    {
        $pageLinks = [];
        for ($page = $this->getFirstPageLinkNumber(); $page <= $this->getLastPageLinkNumber(); $page++) {
            $newPageLink = new PageLink();
            $newPageLink->text = (string)$page;
            $newPageLink->url = $this->getPageLinkUrl($page);
            $pageLinks[] = $newPageLink;
        }

        return $pageLinks;
    }

    public function getPageLinkUrl($page): string
    {
        return $this->getSearchUrl(
            $this->getQuery(),
            $this->getSearchTranslation()->getId(),
            $this->getSortOrder(),
            $page
        );
    }

    public function getSearchUrl(string $query, ?string $translationId = null, ?string $sortOrder = null, int $page = 0): string
    {
        if ($translationId === null) {
            $translationId = $this->getSearchTranslation()->getId();
        }
        if ($sortOrder === null) {
            $sortOrder = $this->getSortOrder();
        }

        $url = '?' . HttpParameters::$query . '=' . urlencode($query) .
            '&' . HttpParameters::$translationId . '=' . urlencode($translationId) .
            '&' . HttpParameters::$sortBy . '=' . urlencode($sortOrder);

        if ($page > 0) {
            $url .= '&' . HttpParameters::$page . '=' . urlencode(strval($page));
        }

        return $url;
    }

    public function getResultsStart(): int
    {
        return ($this->getCurrentPage() - 1) * $this->defaultResultsPerPage + 1;
    }

    public function getResultsPerPage(): int
    {
        return $this->defaultResultsPerPage;
    }

    public function getTotalResults(): int
    {
        return $this->getSearchResult()->total;
    }

    public function getSearchResult(): SearchResult
    {
        if ($this->searchResult === null) {
            $this->searchResult = $this->getSearchService()->search(
                $this->getQuery(),
                $this->getSearchTranslation()->getId(),
                $this->getSortOrder(),
                $this->getCurrentPage(),
                $this->getResultsPerPage()
            );
        }

        return $this->searchResult;
    }

    public function getSortOrderDropDownItems(): array
    {
        $items[] = new DropDownItem($this->strings['/bible/bible-search/sort-order/relevance'], 'relevance');
        $items[] = new DropDownItem($this->strings['/bible/bible-search/sort-order/book'], 'bookorder');

        return $items;
    }

    public function getSearchHits(): array
    {
        $options = $this->getPlugin()->getOptions('general');
        $hits = [];
        foreach ($this->getSearchResult()->hits as $hit) {
            $newHit = new BibleSearchHit();
            $newHit->name = $hit->passage->name;
            // Remove unneeded content: footnotes, verse numbers, etc
            $filteredHtml = $this->getPassageService()->filterPassageContent(
                $hit->passage,
                new PassageFragments(PassageFragments::NONE)
            );
            // Remove any remaining html tags
            $newHit->content = strip_tags($filteredHtml);
            $newHit->url = $this->getUrlBuilder()->getOsisUrl($hit->passage->translation, [$hit->passage]);
            $hits[] = $newHit;
        }

        return $hits;
    }

    /**
     * @throws OnlineBibleException
     */
    private function getUrlBuilder(): BibleReaderUrlBuilderInterface
    {
        return $this->getPlugin()->getUrlBuilder();
    }

    public function getResultsMessage(): string
    {
        if ($this->getQuery() === '') {
            $message = $this->getString('/search/results/no-query');
        } elseif ($this->getTotalResults() < 1) {
            $message = $this->getString('/biblica/templates/rtb/search/noResults');
        } else {
            $message = sprintf(
                '<span class="color-primary">%s</span> %s "<span class="search-phrase">%s</span>"',
                $this->getTotalResults(),
                $this->strings['/bible/bible-search/results/preposition'],
                htmlspecialchars($this->getQuery())
            );
        }

        return $message;
    }

    public function showPageLinks(): bool
    {
        return $this->getLastPage() > 1;
    }

    public function isMainPage(): bool
    {
        return sanitize_text_field($_SERVER['QUERY_STRING']) === '';
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        $heading = apply_filters('biblica_ob_search_heading', '', $this);

        return is_string($heading) ? $heading : '';
    }

    public function getDescription(): string
    {
        return $this->getStrings()['/bible/bible-search/results/meta-description'];
    }

    public function getCanonicalUrl(): string
    {
        return '';
    }

    public function getSearchPlaceholder(): string
    {
        return sprintf(
            $this->strings['/biblica/templates/rtb/search/queryPlaceholder'],
            $this->getSearchTranslation()->getAbbreviation()
        );
    }
}
