<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\Translations\Entities;

class Book
{
    /** @var string[] */
    private ?array $osises = null;
    public string $id;
    public string $name;
    public string $abbreviation;
    public string $osis;
    public string $urlSegment;
    public int $sortOrder;

    /** @var Chapter[] */
    public array $chapters;
    /** @var Chapter[] */
    public array $chaptersByName;
    /** @var Chapter[] */
    public array $chaptersByOsis;

    /**
     * @return array[string]
     */
    public function getOsises(): array
    {
        if ($this->osises === null) {
            $this->osises = [];
            $this->osises[] = $this->osis;
            foreach ($this->chapters as $chapter) {
                foreach ($chapter->getOsises() as $osis) {
                    $this->osises[] = $osis;
                }
            }
        }

        return $this->osises;
    }
}
