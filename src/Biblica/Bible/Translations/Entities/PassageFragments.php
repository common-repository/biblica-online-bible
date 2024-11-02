<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Entities;

class PassageFragments
{
    public const NONE = 0;
    public const CROSS_REFERENCES = 1;
    public const FOOTNOTES = 2;
    public const CHAPTER_NUMBERS = 4;
    public const VERSE_NUMBERS = 8;
    public const HEADINGS = 16;
    public const ALL =
        PassageFragments::CROSS_REFERENCES |
        PassageFragments::FOOTNOTES |
        PassageFragments::CHAPTER_NUMBERS |
        PassageFragments::VERSE_NUMBERS |
        PassageFragments::HEADINGS;

    /** @var int */
    protected $fragments;

    public function __construct($fragments = PassageFragments::NONE)
    {
        $this->setFragments($fragments);
    }

    public function getFragments(): int
    {
        return $this->fragments;
    }

    public function setFragments($fragments)
    {
        if (is_int($fragments)) {
            // Remove invalid bits
            $fragments &= PassageFragments::ALL;
        } elseif (is_object($fragments) && is_a($fragments, PassageFragments)) {
            $fragments = $fragments->getFragments();
        } else {
            return;
        }
        $this->fragments = $fragments;
    }

    public function add($fragments)
    {
        if (is_int($fragments)) {
            // Remove invalid bits
            $fragments &= PassageFragments::ALL;
        } elseif (is_object($fragments) && is_a($fragments, PassageFragments)) {
            $fragments = $fragments->getFragments();
        } else {
            return;
        }
        $this->fragments |= $fragments;
    }

    public function has($fragments): bool
    {
        if (is_int($fragments)) {
            // Remove invalid bits
            $fragments &= PassageFragments::ALL;
        } elseif (is_object($fragments) && is_a($fragments, PassageFragments)) {
            $fragments = $fragments->getFragments();
        } else {
            return false;
        }
        return $fragments === ($this->fragments & $fragments);
    }

    public function equals($fragments): bool
    {
        if (is_int($fragments)) {
            // Remove invalid bits
            $fragments &= PassageFragments::ALL;
        } elseif (is_object($fragments) && is_a($fragments, PassageFragments)) {
            $fragments = $fragments->getFragments();
        } else {
            return false;
        }
        return $this->fragments === $fragments;
    }
}
