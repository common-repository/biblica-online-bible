<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

class Settings
{
    public static string $onlineBibleOptionGroup = 'biblica-ob-options-group';
    public static string $onlineBibleOptionName = 'biblica-ob-options';
    public static array $onlineBibleOptionValues = [
        'bibleApiKey' => '',
        'verifiedBibleApiKey' => null,
        'enablePolyglot' => false,
        'showReaderSearchForm' => false,
        'bibleReaderPageId' => 0,
        'bibleSearchPageId' => 0,
    ];

    // Per site settings
    public static string $bibleApiKey;
    public static ?string $verifiedBibleApiKey = null;
    public static bool $enablePolyglot;
    public static bool $showReaderSearchForm;
    public static int $bibleReaderPageId;
    public static int $bibleSearchPageId;

    /**
     * @var array Populated from the translation settings for the online Bible WordPress plugin.
     *
     * $translations = [
     *      TRANSLATION_ID => [
     *          'enabled' => ENABLED
     *      ]
     * ]
     *
     */
    public static array $translations = [];
    public static ?string $defaultTranslationId = null;

    public static string $translationOptionGroup = 'biblica-ob-translations-group';
    public static string $translationOptionName = 'biblica-ob-translations';
    public static array $translationOptionValues = [
        'defaultTranslationId' => '',
        'translations' => [],
    ];
}
