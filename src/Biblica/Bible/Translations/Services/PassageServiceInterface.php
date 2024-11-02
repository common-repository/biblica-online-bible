<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Services;

use Biblica\Bible\Translations\Entities\Passage;
use Biblica\Bible\Translations\Entities\PassageFragments;

interface PassageServiceInterface
{
    /**
     * Get passages from translations by osis references. Each osis reference
     * must be separated with a comma.
     *
     * @param string $osis The passage osis references to get.
     * @param array $translationIds The translations to get the passages from.
     * @return Passage[] Returns all the matching passages.
     */
    public function getPassages(string $osis, array $translationIds): array;

    public function filterPassageContent(Passage $passage, PassageFragments $include): string;
}
