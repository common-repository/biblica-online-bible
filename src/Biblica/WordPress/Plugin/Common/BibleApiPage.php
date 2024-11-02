<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

use Biblica\Bible\ApiDotBibleApi\Components\PassageService;
use Biblica\Bible\ApiDotBibleApi\Components\SearchService;
use Biblica\Bible\ApiDotBibleApi\Components\TranslationService;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Bible\Translations\Services\PassageServiceInterface;
use Biblica\Bible\Translations\Services\SearchServiceInterface;
use Biblica\Bible\Translations\Services\TranslationServiceInterface;
use Biblica\WordPress\Plugin\OnlineBible\Settings as OnlineBibleSettings;

class BibleApiPage extends WordPressPage
{
    private ?PassageServiceInterface $passageService = null;
    private ?SearchServiceInterface $searchService = null;
    private ?TranslationServiceInterface $translationService = null;
    /** @var string[] */
    protected array $strings = [];
    private array $options = [];
    private ?Translation $translation = null;

    public function __construct(WordPressPlugin $plugin)
    {
        parent::__construct($plugin);

        setlocale(LC_TIME, get_locale());
    }

    /**
     * @param string $optionName
     * @param array $defaultValues
     * @return array
     */
    public function getOptionsByName(string $optionName, array $defaultValues = []): array
    {
        if (!isset($this->options[$optionName])) {
            $this->options[$optionName] = get_option($optionName, $defaultValues);
        }

        if (count($this->options[$optionName]) === 0) {
            $this->options[$optionName] = $defaultValues;
        }
        return $this->options[$optionName];
    }


    /**
     * Returns an array of localized strings for the current locale.
     *
     * @return array
     */
    public function getStrings(): array
    {
        return $this->strings;
    }

    public function getString(string $stringId): string
    {
        return $this->strings[$stringId] ?? '';
    }

    /**
     * Returns a class used to retrieve Bible passages.
     *
     * @return PassageServiceInterface
     */
    public function getPassageService(): PassageServiceInterface
    {
        if ($this->passageService === null) {
            $this->passageService = new PassageService($this->getTranslationService());
        }

        return $this->passageService;
    }

    /**
     * Returns a class used to perform Bible searches.
     *
     * @return SearchServiceInterface
     */
    public function getSearchService(): SearchServiceInterface
    {
        if ($this->searchService === null) {
            $this->searchService = new SearchService($this->getTranslationService());
        }

        return $this->searchService;
    }

    /**
     * Returns a class used to retrieve information about available Bible translations.
     *
     * @return TranslationServiceInterface
     */
    public function getTranslationService(): TranslationServiceInterface
    {
        if ($this->translationService === null) {
            $this->translationService = new TranslationService();
        }

        return $this->translationService;
    }

    /**
     * Strips the translation id from an osis reference and places it in $translationId.
     * Checks if the specified string starts with a translation that is licensed.
     * If no translation is found, the default translation and false is returned. The translation
     * is removed from the returned osis.
     *
     * @param string $input The string to search for a translation.
     * @param string $osis The input with the translation removed.
     * @param string $translationId ID of the translation found.
     * @return bool Returns true if the translation was found and licensed, otherwise false.
     */
    public function parseOsis(string $input, string &$osis, string &$translationId): bool
    {
        $osis = trim($input);

        // Matches:
        // NIV:Gen.1.1
        // NIV: Gen.1.1

        // Does not match:
        // Gen.1.1

        if (preg_match('#(^[A-Za-z0-9-]+):[\s|:]*#ui', $osis, $matches) === 1) {
            $idAndSeparator = $matches[0];
            $osis = str_replace($idAndSeparator, '', $osis);
            $id = $matches[1];
            if (preg_match('#^[A-Fa-f0-9]{16}-\d{2}$#', $id) === 1) {
                // ID is an Api.Bible Bible ID
                $translationId = $id;
            } else {
                // ID is an abbreviation that must be converted to an Api.Bible Bible ID
                $translationId = $this->getTranslationService()->getTranslationIdFromUrlSegment($id);
            }

            if (array_key_exists($translationId, $this->getTranslationService()->getActiveTranslations())) {
                return true;
            }
        }

        // Use fallback translation
        $translationId = OnlineBibleSettings::$defaultTranslationId;

        // This was a fallback language
        return false;
    }

    protected function getBookUrlSegment(): ?string
    {
        return $this->getVar(HttpParameters::$book);
    }

    protected function getChapterName(): ?string
    {
        return $this->getVar(HttpParameters::$chapter);
    }

    public function getTranslation(): ?Translation
    {
        if ($this->translation === null) {
            $this->translation = $this->getTranslationService()->getTranslation($this->getTranslationId());
        }

        return $this->translation;
    }

    protected function getTranslationId(): ?string
    {
        $translationId = $this->getVar(HttpParameters::$translationId, 19);
        if ($translationId !== null) {
            return sanitize_key($translationId);
        }
        $urlSegment = $this->getVar(HttpParameters::$translation);
        if ($urlSegment !== null) {
            return $this->getTranslationService()->getTranslationIdFromUrlSegment($urlSegment);
        }

        return null;
    }

    protected function getCompareTranslationId(): ?string
    {
        if (OnlineBibleSettings::$enablePolyglot === false) {
            return null;
        }

        $compareId = $this->getVar(HttpParameters::$compareId);
        if ($compareId !== null) {
            return $compareId;
        }
        $urlSegment = $this->getVar(HttpParameters::$compare);
        if ($urlSegment !== null) {
            return $this->getTranslationService()->getTranslationIdFromUrlSegment($urlSegment);
        }

        return null;
    }

    protected function getDefaultTranslation(): ?Translation
    {
        $defaultTranslationId = $this->getDefaultTranslationId();

        return $this->getTranslationService()->getTranslation($defaultTranslationId);
    }

    protected function getDefaultTranslationId(): ?string
    {
        return OnlineBibleSettings::$defaultTranslationId;
    }

    protected function getOsis(): ?string
    {
        $osis = $this->getVar(HttpParameters::$osisReference);

        return $osis;
    }
}
