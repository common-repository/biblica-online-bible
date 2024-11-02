<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class Chapter
{
    public string $id;
    public string $name;
    public string $osis;
    public int $numberOfVerses;
    /** @var Verse[] */
    public array $verses;

    public function getUrlSegment(): string
    {
        return mb_strtolower($this->name);
    }

    /**
     * @return string[]
     */
    public function getOsises(): array
    {
        $osises = [];
        $osises[] = $this->osis;

        // TODO: Find efficient way to get number of verses from Api.Bible

        // Disable calculation of osis references for each verse because
        // the number of verses is not readily available in the Api.Bible api.
//        $osisPrefix = $this->osis . '.';
//        for ($verse = 1; $verse <= $this->numberOfVerses; $verse++) {
//            $osises[] = $osisPrefix . $verse;
//        }

        return $osises;
    }
}
