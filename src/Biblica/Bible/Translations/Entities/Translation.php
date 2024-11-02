<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class Translation
{
    /** @var string The unique id of the translation. */
    private string $id;
    /** @var string[] All available names */
    private array $names = [];
    /** @var string|null ID of the default name */
    private ?string $defaultNameId = null;
    /** @var string[] All available abbreviations. */
    private array $abbreviations = [];
    /** @var string|null ID of the default abbreviation. */
    private ?string $defaultAbbreviationId = null;
    /** @var string[] All available descriptions */
    private array $descriptions = [];
    /** @var string|null ID of the default description */
    private ?string $defaultDescriptionId = null;
    /** @var string */
    private string $urlSegment = '';
    /** @var Language The language of the translation */
    private Language $language;
    /** @var string */
    private string $bibleReferenceFormat;
    /** @var Book[] All the available books in the translation. */
    public array $books = [];
    /** @var Book[] All the available books in the translation. */
    public array $booksByUrlSegment = [];
    /** @var Book[] All the available books in the translation. */
    public array $booksByOsis = [];
    /** @var Stylesheet[] */
    private array $styleSheets = [];
    /** @var string|null ID of the default stylesheet */
    private ?string $defaultStyleSheetId = null;
    /** @var AudioBible[] All available audio bibles */
    private array $audioBibles = [];
    /** @var string|null ID of the default audio bibles */
    private ?string $defaultAudioBibleId = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(?string $id = null): ?string
    {
        if ($id === null) {
            $id = $this->defaultNameId;
        }
        return $this->names[$id] ?? null;
    }

    public function getDefaultNameId(): string
    {
        return $this->defaultNameId;
    }

    public function setName(string $id, string $name, bool $makeDefault = false): void
    {
        if ($makeDefault || count($this->names) === 0) {
            $this->defaultNameId = $id;
        }
        $this->names[$id] = $name;
    }

    public function getAbbreviation(?string $id = null): ?string
    {
        if ($id === null) {
            $id = $this->defaultAbbreviationId;
        }
        return $this->abbreviations[$id] ?? null;
    }

    public function setAbbreviation(string $id, string $abbreviation, bool $makeDefault = false): void
    {
        if ($makeDefault || count($this->names) === 0) {
            $this->defaultAbbreviationId = $id;
        }
        $this->abbreviations[$id] = $abbreviation;
    }

    public function getDescription(?string $id = null): ?string
    {
        if ($id === null) {
            $id = $this->defaultDescriptionId;
        }
        return $this->descriptions[$id] ?? null;
    }

    public function setDescription(string $id, string $description, bool $makeDefault = false): void
    {
        if ($makeDefault || count($this->names) === 0) {
            $this->defaultDescriptionId = $id;
        }
        $this->descriptions[$id] = $description;
    }

    public function getUrlSegment(): string
    {
        return $this->urlSegment;
    }

    public function setUrlSegment(string $urlSegment): void
    {
        $this->urlSegment = $urlSegment;
    }

    public function getBibleReferenceFormat(): string
    {
        return $this->bibleReferenceFormat;
    }

    public function setBibleReferenceFormat(string $bibleReferenceFormat): void
    {
        $this->bibleReferenceFormat = $bibleReferenceFormat;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): void
    {
        $this->language = $language;
    }

    public function getStyleSheet(?string $id = null): ?StyleSheet
    {
        if ($id !== null) {
            $styleSheet = $this->styleSheets[$id];
        } elseif ($this->defaultStyleSheetId !== null) {
            $styleSheet = $this->styleSheets[$this->defaultStyleSheetId];
        } else {
            $styleSheet = null;
        }

        return $styleSheet;
    }

    public function setStyleSheet(string $id, StyleSheet $styleSheet, bool $makeDefault = false): void
    {
        if ($makeDefault || count($this->styleSheets) === 0) {
            $this->defaultStyleSheetId = $id;
        }
        $this->styleSheets[$id] = $styleSheet;
    }

    public function getAudioBible(?string $id = null): ?AudioBible
    {
        if ($id !== null) {
            $audioBible = $this->audioBibles[$id];
        } elseif ($this->defaultAudioBibleId !== null) {
            $audioBible = $this->audioBibles[$this->defaultAudioBibleId];
        } else {
            $audioBible = null;
        }

        return $audioBible;
    }

    public function setAudioBible(string $id, audioBible $audioBible, bool $makeDefault = false): void
    {
        if ($makeDefault || count($this->audioBibles) === 0) {
            $this->defaultAudioBibleId = $id;
        }
        $this->audioBibles[$id] = $audioBible;
    }
}
