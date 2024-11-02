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

class BibleWidgetDto
{
    public bool $showWidget;
    public bool $showReadForm;
    public bool $showSearchForm;
    public string $submitUrl;
    public string $bibleReaderUrl;
    public string $bibleReaderUrlType;
    public string $bibleSearchUrl;

    public array $activeTranslations;
    public ?Translation $selectedTranslation = null;
    public string $translationId;
    public ?Book $selectedBook = null;
    public ?Chapter $selectedChapter = null;
    public string $searchPlaceholder;

    public ?string $disabledMessage = null;
}
