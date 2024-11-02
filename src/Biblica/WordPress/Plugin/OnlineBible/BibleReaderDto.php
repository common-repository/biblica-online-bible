<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Passage;
use Biblica\WordPress\Plugin\Common\DropDownItem;
use Biblica\WordPress\Plugin\Common\PageLink;

class BibleReaderDto
{
    public string $missingPassageText;

    public ?Book $book = null;
    public ?Chapter $chapter = null;
    public string $searchTranslationId;

    public ?string $audioBibleId = null;

    /** @var Passage[] */
    public array $passages;
    public array $chapterLinks;
    public string $bibleWrapperClasses;

    public string $title;
    public string $heading;
    public ?PageLink $nextBookLink = null;
    public ?PageLink $previousBookLink = null;
    public ?PageLink $nextChapterLink = null;
    public ?PageLink $previousChapterLink = null;
    /** @var DropDownItem[] */
    public array $bookDropDownItems;
    /** @var DropDownItem[] */
    public array $chapterDropDownItems;
    public $bibleSearchUrl;
    public string $searchPlaceholder;

    public string $ipAddress;

    public ?BibleReaderSectionDto $primarySectionData = null;
    public ?BibleReaderSectionDto $secondarySectionData = null;

    public bool $showSearchForm;
    public bool $showOsis;
    public bool $showMissingPassageText;
    public array $fumsTokens;

    public ?string $disabledMessage = null;
}
