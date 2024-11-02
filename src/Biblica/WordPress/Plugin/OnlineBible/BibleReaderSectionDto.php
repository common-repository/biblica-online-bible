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

class BibleReaderSectionDto
{
    public bool $show;
    public bool $showToolsAndPaging;
    public bool $showMissingPassageText;
    public bool $showPassage;
    public bool $showFooterPaging;
    public bool $showAudioPlayer;
    public bool $showNoAudioMessage;
    public bool $showFootnotes;
    public bool $showCrossReferences;
    public bool $showCloseButton;
    public bool $showCompareTranslationsDropDown;

    public string $translationId;
    public string $translationAbbreviation;
    public string $translationName;
    public ?string $audioBibleId = null;
    public bool $translationIsRightToLeft;
    public ?Book $book = null;
    public ?Chapter $chapter = null;
    public ?Passage $passage = null;
    public int $columns;
    public string $bibleWrapperClasses;

    public string $filteredContent;

    public bool $isPrimaryPassage;

    public ?PageLink $nextBookLink = null;
    public ?PageLink $previousBookLink = null;
    public ?PageLink $nextChapterLink = null;
    public ?PageLink $previousChapterLink = null;

    /** @var DropDownItem[] */
    public array $translationDropDownItems;
    /** @var DropDownItem[] */
    public array $compareTranslationDropDownItems;

    public string $closeUrl;

    public ?string $bibleApi = null;
}
