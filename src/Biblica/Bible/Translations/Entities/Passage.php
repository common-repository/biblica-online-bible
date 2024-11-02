<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class Passage
{
    /** @var string */
    public $name;
    /** @var string */
    public $osis;
    /** @var string Contains formatted HTML and links to cross-references and footnotes. */
    public $content;
    /** @var string */
    public $osisContent;
    /** @var Translation */
    public $translation;
    /** @var Book */
    private $book;
    /** @var Chapter */
    private $chapter;
    /** @var Audio[] */
    public $audio = [];
    /** @var CrossReference[] */
    public $crossReferences = [];
    /** @var Footnote[] */
    public $footnotes = [];
    /** @var string[] */
    public $apiTrackingToken;

    public function getBook(): ?Book
    {
        if ($this->book === null) {
            $references = explode('-', $this->osis);
            $parts = explode('.', $references[0]);
            $bookOsis = $parts[0];
            $this->book = $this->translation->booksByOsis[$bookOsis];
        }

        return $this->book;
    }

    public function getChapter(): ?Chapter
    {
        if ($this->chapter === null) {
            $references = explode('-', $this->osis);
            $parts = explode('.', $references[0]);
            $chapterOsis = $parts[0] . '.' . $parts[1];
            $this->chapter = $this->getBook()->chaptersByOsis[$chapterOsis];
        }

        return $this->chapter;
    }

    public function isCompleteChapter(): bool
    {
        $matchCount = preg_match("#^\\w+\\.\\d+$#ui", $this->osis);

        return $matchCount !== false && $matchCount > 0;
    }
}
