<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Passage;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\WordPress\Plugin\Common\HttpParameters;

class QueryStringBibleReaderUrlBuilder implements BibleReaderUrlBuilderInterface
{
    private ?string $baseUrl = null;

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getBaseUrl(): string
    {
        if ($this->baseUrl === null) {
            $url = get_permalink(Settings::$bibleReaderPageId);
            $this->baseUrl = $url === false ? '' : $url;
        }

        return $this->baseUrl;
    }

    public function getUrlType(): string
    {
        return 'query';
    }

    public function getUrl(
        Translation $translation,
        ?Book $book = null,
        ?Chapter $chapter = null,
        ?Translation $compareTranslation = null
    ): string {
        return $this->getBaseUrl() . $this->getRelativeUrl(
            $translation,
            $book,
            $chapter,
            $compareTranslation,
        );
    }

    public function getRelativeUrl(
        Translation $translation,
        ?Book $book = null,
        ?Chapter $chapter = null,
        ?Translation $compareTranslation = null
    ): string {

        if ($book === null || $chapter === null) {
            $translationValue = rawurlencode($translation->getId());
            $bookValue = rawurlencode($translation->books[0]->urlSegment);
            $chapterValue = rawurlencode($translation->books[0]->chapters[0]->name);
        } else {
            $translationValue = rawurlencode($translation->getId());
            $bookValue = rawurlencode($book->urlSegment);
            $chapterValue = rawurlencode($chapter->name);
        }
        if ($compareTranslation !== null) {
            $compareValue = rawurlencode($compareTranslation->getId());
            $compareParameter = '&' . HttpParameters::$compareId . '=' . $compareValue;
        } else {
            $compareParameter = '';
        }

        $url = '?' . HttpParameters::$translationId . '=' . $translationValue .
            '&' . HttpParameters::$book . '=' . $bookValue .
            '&' . HttpParameters::$chapter . '=' . $chapterValue .
            $compareParameter;

        return mb_strtolower($url);
    }

    /**
     * @param Translation $translation
     * @param array|Passage|string $passages
     * @return string
     */
    public function getOsisUrl(
        Translation $translation,
        $passages
    ): string {
        return $this->getBaseUrl() . $this->getRelativeOsisUrl(
                $translation,
                $passages
            );
    }

    /**
     * @param Translation $translation
     * @param array|Passage|string $passages
     * @return string
     */
    public function getRelativeOsisUrl(
        Translation $translation,
        $passages
    ): string {
        // example url: /bible/?osis=71c6eab17ae5b667-01:gen.1.1
        $osis = $translation->getId() . ':';
        $first = true;
        $passages = is_array($passages) ? $passages : [$passages];
        foreach ($passages as $passage) {
            if ($passage instanceof Passage) {
                $osis .= ($first === true ? '' : ',') . $passage->osis;
                $first = false;
            } elseif (is_string($passage)) {
                $osis .= ($first === true ? '' : ',') . $passage;
                $first = false;
            }
        }

        return '?' . HttpParameters::$osisReference . '=' . rawurlencode($osis);
    }

}
