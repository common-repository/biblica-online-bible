<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Services;

use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Bible\Translations\Entities\TranslationInfo;

interface TranslationServiceInterface
{
    /**
     * Gets the specified translation if it exists in the list of active translations.
     *
     * @param string|null $translationId ID of the translation to return.
     * @return Translation|null Returns the specified translation.
     */
    public function getTranslation(?string $translationId): ?Translation;

    /**
     * Get all translations that are available and enabled in the plugin settings.
     *
     * @return Translation[] Returns all translations with the id as key and the translation as value.
     */
    public function getActiveTranslations(): array;

    /**
     * Returns information on all translations available using the api key specified in the plugin settings.
     *
     * @return TranslationInfo[]
     */
    public function getAvailableTranslations(): array;
}
