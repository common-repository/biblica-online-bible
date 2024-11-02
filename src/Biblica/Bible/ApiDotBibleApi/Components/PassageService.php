<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\ApiDotBibleApi\Components;

use DOMDocument;
use DOMXPath;
use Exception;
use Biblica\Bible\ApiDotBibleApi\Utils\API;
use Biblica\Bible\Translations\Entities\Audio;
use Biblica\Bible\Translations\Entities\BibleReference;
use Biblica\Bible\Translations\Entities\Passage;
use Biblica\Bible\Translations\Entities\PassageFragments;
use Biblica\Bible\Translations\Services\PassageServiceInterface;
use Biblica\Bible\Translations\Services\TranslationServiceInterface;
use Biblica\Util\CacheManager;
use Biblica\Util\LogUtilities;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Contracts\Cache\ItemInterface;

class PassageService implements PassageServiceInterface
{
    use LogUtilities;

    public const CACHE_TAG = 'CacheItems_PassageService';
    public const CACHE_KEY_PASSAGES_PREFIX = 'Passages_';

    private TranslationServiceInterface $translationService;
    private static array $passages = [];

    public function __construct(TranslationServiceInterface $translationService)
    {
        $this->translationService = $translationService;
    }

    private function getPassagesCacheKey(string $osis, string $translationId): string
    {
        return self::CACHE_KEY_PASSAGES_PREFIX . $translationId . '_' . $osis;
    }

    /**
     * @param string $osis
     * @param string[] $translationIds
     * @return Passage[]
     * @throws Exception
     */
    public function getPassages(string $osis, array $translationIds): array
    {
        if (!isset($translationIds[0])) {
            return [];
        }
        $translationId = $translationIds[0];

        $cacheKey = $this->getPassagesCacheKey($osis, $translationId);
        if (isset($this->passages[$cacheKey])) {
            return $this->passages[$cacheKey];
        }

        $getPassagesFunction = function (ItemInterface $item = null) use ($osis, $translationId) {
            if ($item !== null) {
                try {
                    $item->tag(PassageService::CACHE_TAG);
                } catch (CacheException | InvalidArgumentException $e) {
                    $this->log(LogLevel::ERROR, 'Unable to tag cache item. [EXCEPTION: ' . strval($e) . ']');
                }
            }

            $translation = $this->translationService->getTranslation($translationId);
            if ($translation === null) {
                return [];
            }
            $bibleId = $translation->getId();

            $passages = [];

            // default parameter values
            $parameters = [
                'fums-version' => '3',
                'content-type' => 'html',
                'include-titles' => 'true',
                'include-chapter-numbers' => 'true',
                'include-verse-numbers' => 'true',
                'include-notes' => 'true',
                'include-verse-spans' => 'true',
                //'parallels' => '',
                //'use-org-id' => '',
            ];

            $references = mb_split(',|;', $osis);
            for ($index = 0; $index < count($references); $index++) {
                // Convert Bible references to Api.Bible format
                try {
                    $passageReference = new BibleReference($references[$index]);
                } catch (Exception $e) {
                    $this->log(LogLevel::ERROR, strval($e));
                    continue;
                }
                $references[$index] = $passageReference->toOsisString(BibleReference::FORMAT_APIDOTBIBLE);

                $url = sprintf(
                    'bibles/%1$s/passages/%2$s',
                    $bibleId,                       // bibleId
                    urlencode($references[$index])  // passageId
                );

                $response = API::call($url, $parameters);

                if ($response === null || $response->data === null) {
                    return [];
                }

                $data = $response->data;

                $newPassage = new Passage();
                $newPassage->content = $data->content;
                $newPassage->osisContent = $newPassage->content;
                // no cross references in Api.Bible api
                $newPassage->crossReferences = [];
                // footnotes are embedded in Bible content
                $newPassage->footnotes = [];
                $newPassage->name = trim($data->reference);
                $newPassage->translation = $translation;
                $bibleReference = new BibleReference($data->id, BibleReference::FORMAT_APIDOTBIBLE);
                $newPassage->osis = mb_strtolower($data->id);
                $newPassage->apiTrackingToken = $response->meta->fumsToken;

                $audioBible = $translation->getAudioBible();
                if ($audioBible !== null) {
                    $audioOsis = $bibleReference->toOsisString(
                        BibleReference::FORMAT_APIDOTBIBLE,
                        BibleReference::PART_CHAPTER
                    );
                    $newAudio = new Audio();
                    $newAudio->osis = $audioOsis;
                    $newAudio->reader = $audioBible->name;
                    $newPassage->audio[] = $newAudio;
                }

                $passages[] = $newPassage;
            }

            return $passages;
        };


        try {
            self::$passages[$cacheKey] = CacheManager::getObjectCache()->get(
                $cacheKey,
                $getPassagesFunction
            );
        } catch (Exception $e) {
            $this->log(LogLevel::ERROR, 'Cache failure. [EXCEPTION: ' . $e . ']');
            self::$passages[$cacheKey] = $getPassagesFunction();
        }

        return self::$passages[$cacheKey];
    }

    // TODO: Obsolete? Only relevant for BibleGateway markup?
    public function filterPassageContent(Passage $passage, PassageFragments $include): string
    {
        $content = $passage->content;
        if ($content === null || trim($content) === '') {
            return '';
        }

        $doc = new DOMDocument();
        // Calling mb_convert_encoding() is necessary to prevent loadHtml() from scrambling unicode characters
        $utf8Content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        // TODO: suppress warnings without suppressing errors (LIBXML_NOWARNING does not work)
        @$doc->loadHTML($utf8Content, LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING /*| LIBXML_HTML_NOIMPLIED*/);
        $xpath = new DOMXPath($doc);

        if ($include->has(PassageFragments::ALL)) {
            return $doc->saveHTML();
        }

        // Remove headings
        if ($include->equals(PassageFragments::NONE) || !$include->has(PassageFragments::HEADINGS)) {
            // Example: <p class="s1">Allotment for Simeon</p>
            $elements = $xpath->query('.//p[@class="s1"]');
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }

            // Example: <p class="d"><span data-bcv="19.138.1">Of David.</span></p>
            $elements = $xpath->query('.//p[@class="d"]');
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }
        }

        // Remove cross references
        if ($include->equals(PassageFragments::NONE) || !$include->has(PassageFragments::CROSS_REFERENCES)) {
            // Api.Bible API does not currently support cross-references
        }

        // Remove footnotes
        if ($include->equals(PassageFragments::NONE) || !$include->has(PassageFragments::FOOTNOTES)) {
            // Example: <span data-caller="+" id="JOS.19.2!f.1" data-verse-id="JOS.19.2" class="f">...</span>
            $elements = $xpath->query('.//span[@class="f"]');
//            $elements = $xpath->query('.//span[contains(@class, "note") and contains(@class, "f")]');
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }
        }

        // Remove verse numbers
        if ($include->equals(PassageFragments::NONE) || !$include->has(PassageFragments::VERSE_NUMBERS)) {
            // Example: <span class="v" data-number="6">6</span>
            $elements = $xpath->query('.//span[@class="v"]');
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }
        }

        // Remove chapter numbers
        if ($include->equals(PassageFragments::NONE) || !$include->has(PassageFragments::CHAPTER_NUMBERS)) {
            // Example: <h2 class="c" data-number="19">19</h2>
            $elements = $xpath->query('.//h2[@class="c"]');
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }
        }

        return $doc->saveHTML();
    }
}
