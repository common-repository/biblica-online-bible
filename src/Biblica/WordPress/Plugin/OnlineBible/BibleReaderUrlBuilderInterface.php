<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Translation;

interface BibleReaderUrlBuilderInterface
{
    public function setBaseUrl(string $baseUrl);
    public function getUrlType(): string;
    public function getUrl(
        Translation $translation,
        ?Book $book = null,
        ?Chapter $chapter = null,
        ?Translation $compareTranslation = null
    ): string;
    public function getRelativeUrl(
        Translation $translation,
        ?Book $book = null,
        ?Chapter $chapter = null,
        ?Translation $compareTranslation = null
    ): string;
    public function getOsisUrl(Translation $translation, $passages): string;
    public function getRelativeOsisUrl(Translation $translation, $passages): string;
}
