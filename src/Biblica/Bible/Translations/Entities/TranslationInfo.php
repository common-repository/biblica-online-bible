<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class TranslationInfo
{
    /** @var string The unique name of the translation. */
    private string $id;
    /** @var string[] All available names */
    private array $names = [];
    /** @var string ID of the default name */
    private string $defaultNameId = '';
    /** @var string[] All available abbreviations. */
    private array $abbreviations = [];
    /** @var string ID of the default abbreviation. */
    private string $defaultAbbreviationId = '';
    /** @var string[] All available descriptions */
    private array $descriptions = [];
    /** @var string ID of the default description */
    private string $defaultDescriptionId = '';
    /** @var string */
    private string $urlSegment = '';
    /** @var Language The language of the translation */
    private Language $language;
    /** @var string */

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
}
