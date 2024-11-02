<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\ApiDotBibleApi\Components;

use Exception;
use Biblica\Bible\ApiDotBibleApi\Utils\API;
use Biblica\Bible\Translations\Entities\AudioBible;
use Biblica\Bible\Translations\Entities\BibleReference;
use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Language;
use Biblica\Bible\Translations\Entities\StyleSheet;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Bible\Translations\Entities\TranslationInfo;
use Biblica\Bible\Translations\Services\TranslationServiceInterface;
use Biblica\Util\CacheManager;
use Biblica\Util\LogUtilities;
use Biblica\WordPress\Plugin\OnlineBible\Settings as OnlineBibleSettings;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Contracts\Cache\ItemInterface;

class TranslationService implements TranslationServiceInterface
{
    use LogUtilities;

    public const CACHE_TAG = 'CacheItems_TranslationService';
    public const CACHE_KEY_AVAILABLE_TRANSLATIONS = 'AvailableTranslations';
    public const CACHE_KEY_ACTIVE_TRANSLATIONS = 'ActiveTranslations';

    /** @var TranslationInfo[]|null */
    private static ?array $availableTranslations = null;
    /** @var Translation[]|null */
    private static ?array $activeTranslations = null;
    /** @var Translation[]|null */
    private static ?array $activeTranslationsByUrlSegment = null;
    /** @var string[]|null */
    private static ?array $translationList = null;

    /**
     * @return string[]
     */
    public function getTranslationList(): array
    {
        if (self::$translationList === null) {
            $translationList = [];
            $availableTranslations = $this->getAvailableTranslations();
            foreach ($availableTranslations as $translationInfo) {
                $translationList[] = $translationInfo->getId();
            }

            self::$translationList = $translationList;
        }

        return self::$translationList;
    }

    /**
     * @throws Exception
     */
    public function isAvailable(string $translationId): bool
    {
        return isset($this->getAvailableTranslations()[$translationId]);
    }

    public function isEnabled(string $translationId): bool
    {
        if (!isset(OnlineBibleSettings::$translations[$translationId]['enabled'])) {
            return false;
        }

        return OnlineBibleSettings::$translations[$translationId]['enabled'];
    }

    /**
     * @param string|null $translationId
     * @return Translation|null
     * @throws Exception
     */
    public function getTranslation(?string $translationId): ?Translation
    {
        $translation = null;

        $activeTranslations = $this->getActiveTranslations();
        if ($translationId !== null && trim($translationId) !== '') {
            $translation = $activeTranslations[$translationId] ?? null;
        }

        return $translation;
    }

    private function loadTranslation(string $translationId): ?object
    {
        $translationUrl = 'bibles/' . $translationId;
        $translationResponse = API::callAndCache($translationUrl);

        return $translationResponse->data;
    }

    public function getTranslationIdFromUrlSegment(string $urlSegment): ?string
    {
        $translations = $this->getActiveTranslationsByUrlSegment();
        if (isset($translations[$urlSegment])) {
            $translationId = $translations[$urlSegment]->getId();
        } else {
            $translationId = null;
        }

        return $translationId;
    }

    /**
     * @return Translation[]
     */
    public function getActiveTranslationsByUrlSegment(): array
    {
        if (self::$activeTranslationsByUrlSegment !== null) {
            return self::$activeTranslationsByUrlSegment;
        }

        $activeTranslations = $this->getActiveTranslations();
        self::$activeTranslationsByUrlSegment = [];
        foreach ($activeTranslations as $translation) {
            self::$activeTranslationsByUrlSegment[$translation->getUrlSegment()] = $translation;
        }

        return self::$activeTranslationsByUrlSegment;
    }

    /**
     * @return Translation[]
     */
    public function getActiveTranslations(): array
    {
        if (self::$activeTranslations !== null) {
            return self::$activeTranslations;
        }

        $getActiveTranslationsFunction = function (?ItemInterface $item = null) {
            if ($item !== null) {
                try {
                    $item->tag(TranslationService::CACHE_TAG);
                } catch (CacheException | InvalidArgumentException $e) {
                    $this->log(LogLevel::ERROR, 'Unable to tag cache item. [EXCEPTION: ' . $e . ']');
                }
            }

            $this->log(LogLevel::DEBUG, '[Cache Miss: Active Translations]');

            $activeTranslations = [];
            foreach ($this->getAvailableTranslations() as $translationInfo) {
                if ($this->isEnabled($translationInfo->getId())) {
                    $translationData = $this->loadTranslation($translationInfo->getId());
                    $newTranslation = $this->createTranslation($translationData);
                    $activeTranslations[$newTranslation->getId()] = $newTranslation ;
                }
            }

            if (count($activeTranslations) === 0 && $item !== null) {
                $item->expiresAfter(1);
            }

            return $activeTranslations;
        };

        try {
            self::$activeTranslations = CacheManager::getObjectCache()->get(
                self::CACHE_KEY_ACTIVE_TRANSLATIONS,
                $getActiveTranslationsFunction
            );
        } catch (Exception $e) {
            self::$activeTranslations = $getActiveTranslationsFunction();
        }

        return self::$activeTranslations;
    }

    private function createChapter(object $chapter): Chapter
    {
        $newChapter = new Chapter();
        $newChapter->id = $chapter->id;
        $newChapter->name = strval($chapter->number);
        $newChapter->osis = mb_strtolower($chapter->id);
        // TODO: How to set 'numberOfVerses'? Used by Chapter->getOsises() and Bible site map
        $newChapter->numberOfVerses = 0;

        return $newChapter;
    }

    private function createBook(object $book, int $sortOrder): Book
    {
        $newBook = new Book();
        $newBook->id = $book->id;
        $newBook->name = $book->name;
        $newBook->abbreviation = $book->abbreviation;
        $newBook->osis = mb_strtolower($book->id);
        $newBook->sortOrder = $sortOrder;
        $newBook->urlSegment = mb_strtolower(str_replace(' ', '-', $book->name));
        foreach ($book->chapters as $chapter) {
            // Skip non-numeric chapter numbers. They
            // represent non-scriptural content (e.g. notes)
            if (!is_numeric($chapter->number)) {
                continue;
            }

            $newChapter = $this->createChapter($chapter);

            $newBook->chapters[] = $newChapter;
            $newBook->chaptersByName[$newChapter->name] = $newChapter;
            $newBook->chaptersByOsis[$newChapter->osis] = $newChapter;
        }

        return $newBook;
    }

    /**
     * @param object $translation
     * @return Translation
     */
    private function createTranslation(object $translation): Translation
    {
        $booksUrl = 'bibles/' . $translation->id . '/books';
        $booksParameters = [
            'include-chapters' => 'true'
        ];
        $booksResponse = API::callAndCache($booksUrl, $booksParameters);
        $books = $booksResponse->data;

        $newTranslation = new Translation($translation->id);

        $customName = OnlineBibleSettings::$translations[$translation->id]['customName'] ?? null;
        if ($customName !== null) {
            $defaultName = 'custom';
            $newTranslation->setName('custom', $customName, true);
        } else {
            $defaultName = 'eng';
        }
        $newTranslation->setName('eng', $translation->name, $defaultName === 'eng');
        $newTranslation->setName('local', $translation->nameLocal, $defaultName === 'local');

        $customAbbreviation = OnlineBibleSettings::$translations[$translation->id]['customAbbreviation'] ?? null;
        if ($customAbbreviation !== null) {
            $defaultAbbreviation = 'custom';
            $newTranslation->setAbbreviation('custom', $customAbbreviation, true);
        } else {
            $defaultAbbreviation = 'eng';
        }
        $newTranslation->setAbbreviation('eng', $translation->abbreviation, $defaultAbbreviation === 'eng');
        $newTranslation->setAbbreviation('local', $translation->abbreviationLocal, $defaultAbbreviation === 'local');

        $newTranslation->setUrlSegment($this->getUrlSegment($newTranslation->getAbbreviation()));
        $newTranslation->setDescription('eng', $translation->description ?? '', true);
        $newTranslation->setDescription('local', $translation->descriptionLocal ?? '');
        $newTranslation->setBibleReferenceFormat(BibleReference::FORMAT_APIDOTBIBLE);

        $newStyleSheet = new StyleSheet();
        $newStyleSheet->default = true;
        $newStyleSheet->wrapperClasses = 'scripture-styles';
        $newStyleSheet->url = '/lib/api-bible/scripture-styles.css';
        $newTranslation->setStyleSheet('default', $newStyleSheet, true);

        foreach ($translation->audioBibles as $audioBible) {
            $newAudioBible = new AudioBible();
            $newAudioBible->id = $audioBible->id;
            $newAudioBible->name = $audioBible->name;
            $newAudioBible->nameLocal = $audioBible->nameLocal;
            $newTranslation->setAudioBible($newAudioBible->id, $newAudioBible);
        }

        $newLanguage = new Language();
        $newLanguage->iso = $translation->language->id;
        $newLanguage->name = $translation->language->name;
        $newLanguage->nameLocal = $translation->language->nameLocal;
        $newLanguage->script = $translation->language->script;
        $newLanguage->direction = $translation->language->scriptDirection;
        $newLanguage->isRightToLeft = $translation->language->scriptDirection === 'RTL';
        $newTranslation->setLanguage($newLanguage);

        $bookSortOrder = 1;
        foreach ($books as $book) {
            $newBook = $this->createBook($book, $bookSortOrder);
            $bookSortOrder++;

            $newTranslation->books[] = $newBook;
            $newTranslation->booksByUrlSegment[$newBook->urlSegment] = $newBook;
            $newTranslation->booksByOsis[$newBook->osis] = $newBook;
        }

        return $newTranslation;
    }

    private function getUrlSegment(string $string): string
    {
        return mb_strtolower(str_replace(' ', '-', $string));
    }

    /**
     * @param object $translation
     * @return TranslationInfo
     */
    private function createTranslationInfo(object $translation): TranslationInfo
    {
        $newTranslationInfo = new TranslationInfo($translation->id);

        $customName = OnlineBibleSettings::$translations[$translation->id]['customName'] ?? null;
        if ($customName !== null) {
            $defaultName = 'custom';
            $newTranslationInfo->setName('custom', $customName, true);
        } else {
            $defaultName = 'eng';
        }
        $newTranslationInfo->setName('eng', $translation->name, $defaultName === 'eng');
        $newTranslationInfo->setName('local', $translation->nameLocal, $defaultName === 'local');

        $customAbbreviation = OnlineBibleSettings::$translations[$translation->id]['customAbbreviation'] ?? null;
        if ($customAbbreviation !== null) {
            $defaultAbbreviation = 'custom';
            $newTranslationInfo->setAbbreviation('custom', $customAbbreviation, true);
        } else {
            $defaultAbbreviation = 'eng';
        }
        $newTranslationInfo->setAbbreviation('eng', $translation->abbreviation, $defaultAbbreviation === 'eng');
        $newTranslationInfo->setAbbreviation('local', $translation->abbreviationLocal, $defaultAbbreviation === 'local');

        $newTranslationInfo->setUrlSegment($this->getUrlSegment($newTranslationInfo->getAbbreviation()));
        $newTranslationInfo->setDescription('eng', $translation->description ?? '', true);
        $newTranslationInfo->setDescription('local', $translation->descriptionLocal ?? '');
        $newTranslationInfo->setBibleReferenceFormat(BibleReference::FORMAT_APIDOTBIBLE);

        $newLanguage = new Language();
        $newLanguage->iso = $translation->language->id;
        $newLanguage->name = $translation->language->name;
        $newLanguage->nameLocal = $translation->language->nameLocal;
        $newLanguage->script = $translation->language->script;
        $newLanguage->direction = $translation->language->scriptDirection;
        $newLanguage->isRightToLeft = $translation->language->scriptDirection === 'RTL';
        $newTranslationInfo->setLanguage($newLanguage);

        return $newTranslationInfo;
    }

    /**
     * @return TranslationInfo[]
     */
    public function getAvailableTranslations(): array
    {
        if (self::$availableTranslations !== null) {
            return self::$availableTranslations;
        }

        $getAvailableTranslationsFunction = function (?ItemInterface $item = null) {
            if ($item !== null) {
                try {
                    $item->tag(TranslationService::CACHE_TAG);
                } catch (CacheException | InvalidArgumentException $e) {
                    $this->log(LogLevel::ERROR, 'Unable to tag cache item. [EXCEPTION: ' . strval($e) . ']');
                }
            }

            $this->log(LogLevel::DEBUG, '[Cache Miss: Available Translations]');

            $availableTranslationsUrl = 'bibles';
            $availableTranslationsParameters = [
                'include-full-details' => 'false'
            ];
            $response = API::call($availableTranslationsUrl, $availableTranslationsParameters);

            $availableTranslations = [];

            if (!is_object($response)) {
                $this->log(
                    LogLevel::ERROR,
                    "[API ERROR: Url: $availableTranslationsUrl, Method: " . __METHOD__ .
                    ", Invalid value returned from API::call().] "
                );
                $exception = new Exception();
                $trace = "[Call Stack: " . $exception->getTraceAsString() . "] ";
                $this->log(LogLevel::DEBUG, $trace);
                if ($item !== null) {
                    $item->expiresAfter(1);
                }
            } else {
                foreach ($response->data as $translation) {
                    $newTranslationInfo = $this->createTranslationInfo($translation);
                    $availableTranslations[$newTranslationInfo->getId()] = $newTranslationInfo;
                }
            }

            if (count($availableTranslations) === 0 && $item !== null) {
                $item->expiresAfter(1);
            }

            return $availableTranslations;
        };

        try {
            self::$availableTranslations = CacheManager::getObjectCache()->get(
                self::CACHE_KEY_AVAILABLE_TRANSLATIONS,
                $getAvailableTranslationsFunction
            );
        } catch (Exception $exception) {
            self::$availableTranslations = $getAvailableTranslationsFunction();
        }

        return self::$availableTranslations;
    }
}
