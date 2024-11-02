<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Entities;

use Exception;

class BibleReference
{
    /** @var string */
    private $originalReference;
    /** @var int */
    public $bookNumber;
    /** @var int */
    public $chapter;
    /** @var int */
    public $verse;
    /** @var int */
    public $bookNumberEnd;
    /** @var int */
    public $chapterEnd;
    /** @var int */
    public $verseEnd;

    /** @var string Example: JHN */
    public const PART_BOOK = 'PART_BOOK';
    /** @var string Example: JHN.3 */
    public const PART_CHAPTER = 'PART_CHAPTER';
    /** @var string Example: JHN.3.16 */
    public const PART_VERSE = 'PART_VERSE';
    /** @var string Example: JHN.3.16-JHN.3.17 */
    public const PART_ALL = 'PART_ALL';

    /** @var string Example: 1Kgs.3.16 */
    public const FORMAT_BIBLEGATEWAY = 'FORMAT_BIBLEGATEWAY';
    /** @var string Example: 1KI.3.16 */
    public const FORMAT_APIDOTBIBLE = 'FORMAT_APIDOTBIBLE';
    /** @var string Example: 1 Kings 3:16 */
    public const FORMAT_STANDARD = 'FORMAT_STANDARD';

    /** @var array Maps book names to book numbers based on Bible reference format and language. */
    private static $bookNames = [
        BibleReference::FORMAT_BIBLEGATEWAY => [
            'gen' => 1,
            'exod' => 2,
            'lev' => 3,
            'num' => 4,
            'deut' => 5,
            'josh' => 6,
            'judg' => 7,
            'ruth' => 8,
            '1sam' => 9,
            '2sam' => 10,
            '1kgs' => 11,
            '2kgs' => 12,
            '1chr' => 13,
            '2chr' => 14,
            'ezra' => 15,
            'neh' => 16,
            'esth' => 17,
            'job' => 18,
            'ps' => 19,
            'prov' => 20,
            'eccl' => 21,
            'song' => 22,
            'isa' => 23,
            'jer' => 24,
            'lam' => 25,
            'ezek' => 26,
            'dan' => 27,
            'hos' => 28,
            'joel' => 29,
            'amos' => 30,
            'obad' => 31,
            'jonah' => 32,
            'mic' => 33,
            'nah' => 34,
            'hab' => 35,
            'zeph' => 36,
            'hag' => 37,
            'zech' => 38,
            'mal' => 39,
            'matt' => 40,
            'mark' => 41,
            'luke' => 42,
            'john' => 43,
            'acts' => 44,
            'rom' => 45,
            '1cor' => 46,
            '2cor' => 47,
            'gal' => 48,
            'eph' => 49,
            'phil' => 50,
            'col' => 51,
            '1thess' => 52,
            '1thes' => 52,  // alternate
            '2thess' => 53,
            '2thes' => 53,  // alternate
            '1tim' => 54,
            '2tim' => 55,
            'titus' => 56,
            'phlm' => 57,
            'heb' => 58,
            'jas' => 59,
            '1pet' => 60,
            '2pet' => 61,
            '1john' => 62,
            '2john' => 63,
            '3john' => 64,
            'jude' => 65,
            'rev' => 66,
        ],
        BibleReference::FORMAT_APIDOTBIBLE => [
            'gen' => 1,
            'exo' => 2,
            'lev' => 3,
            'num' => 4,
            'deu' => 5,
            'jos' => 6,
            'jdg' => 7,
            'rut' => 8,
            '1sa' => 9,
            '2sa' => 10,
            '1ki' => 11,
            '2ki' => 12,
            '1ch' => 13,
            '2ch' => 14,
            'ezr' => 15,
            'neh' => 16,
            'est' => 17,
            'job' => 18,
            'psa' => 19,
            'pro' => 20,
            'ecc' => 21,
            'sng' => 22,
            'isa' => 23,
            'jer' => 24,
            'lam' => 25,
            'ezk' => 26,
            'dan' => 27,
            'hos' => 28,
            'jol' => 29,
            'amo' => 30,
            'oba' => 31,
            'jon' => 32,
            'mic' => 33,
            'nam' => 34,
            'hab' => 35,
            'zep' => 36,
            'hag' => 37,
            'zec' => 38,
            'mal' => 39,
            'mat' => 40,
            'mrk' => 41,
            'luk' => 42,
            'jhn' => 43,
            'act' => 44,
            'rom' => 45,
            '1co' => 46,
            '2co' => 47,
            'gal' => 48,
            'eph' => 49,
            'php' => 50,
            'col' => 51,
            '1th' => 52,
            '2th' => 53,
            '1ti' => 54,
            '2ti' => 55,
            'tit' => 56,
            'phm' => 57,
            'heb' => 58,
            'jas' => 59,
            '1pe' => 60,
            '2pe' => 61,
            '1jn' => 62,
            '2jn' => 63,
            '3jn' => 64,
            'jud' => 65,
            'rev' => 66,
            // Apocrypha
            '1es' => 67,
            '2es' => 68,
            'tob' => 69,
            'jdt' => 70,
            'esg' => 71,
            'wis' => 72,
            'sir' => 73,
            'bar' => 74,
            's3y' => 75,
            'sus' => 76,
            'bel' => 77,
            'man' => 78,
            '1ma' => 79,
            '2ma' => 80,
        ],
        BibleReference::FORMAT_STANDARD => [
            'NIV' => [
                'genesis' => 1,
                'exodus' => 2,
                'leviticus' => 3,
                'numbers' => 4,
                'deuteronomy' => 5,
                'joshua' => 6,
                'judges' => 7,
                'ruth' => 8,
                '1 samuel' => 9,
                '2 samuel' => 10,
                '1 kings' => 11,
                '2 kings' => 12,
                '1 chronicles' => 13,
                '2 chronicles' => 14,
                'ezra' => 15,
                'nehemiah' => 16,
                'esther' => 17,
                'job' => 18,
                'psalms' => 19,
                'psalm' => 19, // alternate form
                'proverbs' => 20,
                'ecclesiastes' => 21,
                'song of songs' => 22,
                'song' => 22,  // alternate form
                'song of solomon' => 22,  // alternate form
                'isaiah' => 23,
                'jeremiah' => 24,
                'lamentations' => 25,
                'ezekiel' => 26,
                'daniel' => 27,
                'hosea' => 28,
                'joel' => 29,
                'amos' => 30,
                'obadiah' => 31,
                'jonah' => 32,
                'micah' => 33,
                'nahum' => 34,
                'habakkuk' => 35,
                'zephaniah' => 36,
                'haggai' => 37,
                'zechariah' => 38,
                'malachi' => 39,
                'matthew' => 40,
                'mark' => 41,
                'luke' => 42,
                'john' => 43,
                'acts' => 44,
                'romans' => 45,
                '1 corinthians' => 46,
                '2 corinthians' => 47,
                'galatians' => 48,
                'ephesians' => 49,
                'philippians' => 50,
                'colossians' => 51,
                '1 thessalonians' => 52,
                '2 thessalonians' => 53,
                '1 timothy' => 54,
                '2 timothy' => 55,
                'titus' => 56,
                'philemon' => 57,
                'hebrews' => 58,
                'james' => 59,
                '1 peter' => 60,
                '2 peter' => 61,
                '1 john' => 62,
                '2 john' => 63,
                '3 john' => 64,
                'jude' => 65,
                'revelation' => 66,
            ]
        ]
    ];

    /** @var array Maps book numbers to book names based on Bible reference format and language. */
    private static $bookNumbers = [
        BibleReference::FORMAT_BIBLEGATEWAY => [
            1 => 'gen',
            2 => 'exod',
            3 => 'lev',
            4 => 'num',
            5 => 'deut',
            6 => 'josh',
            7 => 'judg',
            8 => 'ruth',
            9 => '1sam',
            10 => '2sam',
            11 => '1kgs',
            12 => '2kgs',
            13 => '1chr',
            14 => '2chr',
            15 => 'ezra',
            16 => 'neh',
            17 => 'esth',
            18 => 'job',
            19 => 'ps',
            20 => 'prov',
            21 => 'eccl',
            22 => 'song',
            23 => 'isa',
            24 => 'jer',
            25 => 'lam',
            26 => 'ezek',
            27 => 'dan',
            28 => 'hos',
            29 => 'joel',
            30 => 'amos',
            31 => 'obad',
            32 => 'jonah',
            33 => 'mic',
            34 => 'nah',
            35 => 'hab',
            36 => 'zeph',
            37 => 'hag',
            38 => 'zech',
            39 => 'mal',
            40 => 'matt',
            41 => 'mark',
            42 => 'luke',
            43 => 'john',
            44 => 'acts',
            45 => 'rom',
            46 => '1cor',
            47 => '2cor',
            48 => 'gal',
            49 => 'eph',
            50 => 'phil',
            51 => 'col',
            52 => '1thess',
            53 => '2thess',
            54 => '1tim',
            55 => '2tim',
            56 => 'titus',
            57 => 'phlm',
            58 => 'heb',
            59 => 'jas',
            60 => '1pet',
            61 => '2pet',
            62 => '1john',
            63 => '2john',
            64 => '3john',
            65 => 'jude',
            66 => 'rev',
        ],
        BibleReference::FORMAT_APIDOTBIBLE => [
            1 => 'gen',
            2 => 'exo',
            3 => 'lev',
            4 => 'num',
            5 => 'deu',
            6 => 'jos',
            7 => 'jdg',
            8 => 'rut',
            9 => '1sa',
            10 => '2sa',
            11 => '1ki',
            12 => '2ki',
            13 => '1ch',
            14 => '2ch',
            15 => 'ezr',
            16 => 'neh',
            17 => 'est',
            18 => 'job',
            19 => 'psa',
            20 => 'pro',
            21 => 'ecc',
            22 => 'sng',
            23 => 'isa',
            24 => 'jer',
            25 => 'lam',
            26 => 'ezk',
            27 => 'dan',
            28 => 'hos',
            29 => 'jol',
            30 => 'amo',
            31 => 'oba',
            32 => 'jon',
            33 => 'mic',
            34 => 'nam',
            35 => 'hab',
            36 => 'zep',
            37 => 'hag',
            38 => 'zec',
            39 => 'mal',
            40 => 'mat',
            41 => 'mrk',
            42 => 'luk',
            43 => 'jhn',
            44 => 'act',
            45 => 'rom',
            46 => '1co',
            47 => '2co',
            48 => 'gal',
            49 => 'eph',
            50 => 'php',
            51 => 'col',
            52 => '1th',
            53 => '2th',
            54 => '1ti',
            55 => '2ti',
            56 => 'tit',
            57 => 'phm',
            58 => 'heb',
            59 => 'jas',
            60 => '1pe',
            61 => '2pe',
            62 => '1jn',
            63 => '2jn',
            64 => '3jn',
            65 => 'jud',
            66 => 'rev',
            // Apocrypha
            67 => '1es',
            68 => '2es',
            69 => 'tob',
            70 => 'jdt',
            71 => 'esg',
            72 => 'wis',
            73 => 'sir',
            74 => 'bar',
            75 => 's3y',
            76 => 'sus',
            77 => 'bel',
            78 => 'man',
            79 => '1ma',
            80 => '2ma',
        ],
        BibleReference::FORMAT_STANDARD => [
            'NIV' => [
                1 => 'Genesis',
                2 => 'Exodus',
                3 => 'Leviticus',
                4 => 'Numbers',
                5 => 'Deuteronomy',
                6 => 'Joshua',
                7 => 'Judges',
                8 => 'Ruth',
                9 => '1 Samuel',
                10 => '2 Samuel',
                11 => '1 Kings',
                12 => '2 Kings',
                13 => '1 Chronicles',
                14 => '2 Chronicles',
                15 => 'Ezra',
                16 => 'Nehemiah',
                17 => 'Esther',
                18 => 'Job',
                19 => 'Psalms',
                20 => 'Proverbs',
                21 => 'Ecclesiastes',
                22 => 'Song of Songs',
                23 => 'Isaiah',
                24 => 'Jeremiah',
                25 => 'Lamentations',
                26 => 'Ezekiel',
                27 => 'Daniel',
                28 => 'Hosea',
                29 => 'Joel',
                30 => 'Amos',
                31 => 'Obadiah',
                32 => 'Jonah',
                33 => 'Micah',
                34 => 'Nahum',
                35 => 'Habakkuk',
                36 => 'Zephaniah',
                37 => 'Haggai',
                38 => 'Zechariah',
                39 => 'Malachi',
                40 => 'Matthew',
                41 => 'Mark',
                42 => 'Luke',
                43 => 'John',
                44 => 'Acts',
                45 => 'Romans',
                46 => '1 Corinthians',
                47 => '2 Corinthians',
                48 => 'Galatians',
                49 => 'Ephesians',
                50 => 'Philippians',
                51 => 'Colossians',
                52 => '1 Thessalonians',
                53 => '2 Thessalonians',
                54 => '1 Timothy',
                55 => '2 Timothy',
                56 => 'Titus',
                57 => 'Philemon',
                58 => 'Hebrews',
                59 => 'James',
                60 => '1 Peter',
                61 => '2 Peter',
                62 => '1 John',
                63 => '2 John',
                64 => '3 John',
                65 => 'Jude',
                66 => 'Revelation',
            ]
        ]
    ];

    /**
     * BibleReference constructor.
     * @param string $reference
     * @param string|null $format
     * @throws Exception
     */
    public function __construct(string $reference, string $format = null)
    {
        // Remove right-to-left marker
        $reference = preg_replace('/\x{200F}/u', '', $reference);

        // Skip book introduction
        $reference = preg_replace('/\.intro\.0-/', '.1.1-', $reference);

        $this->originalReference = $reference;
        $reference = trim($reference);

        $standardRegEx = '#^((?:[123]{1}\s+)?[a-zA-Z]\w*)(?:\s+(\d{1,3})?(?:\s*:\s*(\d{1,3}))?)?' .
            '(?:\s*[-–]\s*(?:((?:[123]{1}\s+)?[a-zA-Z]\w*)\s+)?(?:(\d{1,3})\s*:\s*)?(\d{1,3}))?$#u';
        $osisRegEx = '#^([1-3a-zA-Z]{2,6})(?:\.(\d{1,3}))?(?:\.(\d{1,3}))?(?:[-–]([1-3a-zA-Z]{2,6})' .
            '(?:\.(\d{1,3}))(?:\.(\d{1,3}))?)?$#u';
        $parseSuccessful = false;
        if ($format === BibleReference::FORMAT_STANDARD) {
            $parseSuccessful = preg_match($standardRegEx, $reference, $matches, PREG_UNMATCHED_AS_NULL) === 1;
        } elseif ($format === BibleReference::FORMAT_BIBLEGATEWAY || $format === BibleReference::FORMAT_APIDOTBIBLE) {
            $parseSuccessful = preg_match($osisRegEx, $reference, $matches, PREG_UNMATCHED_AS_NULL) === 1;
        } else {
            if (preg_match($standardRegEx, $reference, $matches, PREG_UNMATCHED_AS_NULL) === 1) {
                $parseSuccessful = true;
                $format = BibleReference::FORMAT_STANDARD;
                $matches[2] = $matches[2] === '' ? null : $matches[2];
                $matches[3] = $matches[3] === '' ? null : $matches[3];
                $matches[4] = $matches[4] === '' ? null : $matches[4];
                $matches[5] = $matches[5] === '' ? null : $matches[5];
                $matches[6] = $matches[6] === '' ? null : $matches[6];
            } elseif (preg_match($osisRegEx, $reference, $matches, PREG_UNMATCHED_AS_NULL) === 1) {
                $parseSuccessful = true;
                if (
                    array_key_exists(mb_strtolower($matches[1]), self::$bookNames[BibleReference::FORMAT_APIDOTBIBLE])
                ) {
                    $format = BibleReference::FORMAT_APIDOTBIBLE;
                } elseif (
                    array_key_exists(mb_strtolower($matches[1]), self::$bookNames[BibleReference::FORMAT_BIBLEGATEWAY])
                ) {
                    $format = BibleReference::FORMAT_BIBLEGATEWAY;
                }
            }
        }

        // If we can't figure out the book number from the book name, the parse has failed
        if ($matches[1] !== null && $this->getBookNumber($matches[1], $format) === null) {
            $parseSuccessful = false;
        }
        if ($matches[4] !== null && $this->getBookNumber($matches[4], $format) === null) {
            $parseSuccessful = false;
        }

        $parseErrorMessage = 'Could not parse Bible reference: ';
        if ($parseSuccessful) {
            $this->bookNumber = $this->getBookNumber($matches[1], $format);
            $this->chapter = $matches[2];
            $this->verse = $matches[3];
            $this->bookNumberEnd = $matches[4] === null ? null : $this->getBookNumber($matches[4], $format);
            $this->chapterEnd = $matches[5];
            $this->verseEnd = $matches[6];

            // Correct situations where a chapter number is captured as a verse number
            if ($this->verseEnd !== null) {
                if ($this->bookNumberEnd !== null && $this->chapterEnd === null) {
                    // Example: John 3-John 4
                    $this->chapterEnd = $this->verseEnd;
                    $this->verseEnd = null;
                } elseif (
                    $this->bookNumberEnd === null &&
                    $this->chapterEnd === null &&
                    $this->verse === null &&
                    $this->chapter !== null
                ) {
                    // Example: John 3-4
                    $this->bookNumberEnd = $this->bookNumber;
                    $this->chapterEnd = $this->verseEnd;
                    $this->verseEnd = null;
                }
            }
        } else {
            throw new Exception($parseErrorMessage . $reference);
        }
    }

    private function getBookNumber(string $bookName, string $format): ?int
    {
        $lowerBookName = mb_strtolower($bookName);
        if ($format === BibleReference::FORMAT_STANDARD) {
            $bookNumber = self::$bookNames[BibleReference::FORMAT_STANDARD]['NIV'][$lowerBookName];
            if ($bookNumber === null) {
                $bookNumber = self::$bookNames[BibleReference::FORMAT_BIBLEGATEWAY][$lowerBookName];
            }
            if ($bookNumber === null) {
                $bookNumber = self::$bookNames[BibleReference::FORMAT_APIDOTBIBLE][$lowerBookName];
            }
        } else {
            $bookNumber = self::$bookNames[$format][$lowerBookName];
        }

        return $bookNumber;
    }

    private function getBookName(int $bookNumber, string $format, string $translationId = 'NIV')
    {
        if ($format === BibleReference::FORMAT_STANDARD) {
            $bookName = self::$bookNumbers[$format][$translationId][$bookNumber];
        } else {
            $bookName = self::$bookNumbers[$format][$bookNumber];
        }
        return $bookName;
    }

    public function toString(string $translationId = 'NIV'): string
    {
        $string = $this->getBookName($this->bookNumber, BibleReference::FORMAT_STANDARD, $translationId);
        if ($this->chapter !== null) {
            $string .= ' ' . $this->chapter;
        }
        if ($this->verse !== null) {
            $string .= ':' . $this->verse;
        }
        if ($this->bookNumberEnd !== null || $this->chapterEnd !== null || $this->verseEnd !== null) {
            $string .= '-';
        }
        if ($this->bookNumberEnd !== null) {
            $string .= $this->getBookName($this->bookNumberEnd, BibleReference::FORMAT_STANDARD, $translationId);
        }
        if ($this->chapterEnd !== null) {
            $separator = $this->bookNumberEnd !== null ? ' ' : '';
            $string .= $separator . $this->chapterEnd;
        }
        if ($this->verseEnd !== null) {
            $chapterVerseSeparator = $this->chapterEnd !== null ? ':' : '';
            $string .= $chapterVerseSeparator . $this->verseEnd;
        }

        return $string;
    }

    public function toOsisString(string $format, string $part = null): string
    {
        if ($part === null) {
            $part = self::PART_ALL;
        }

        // string: JHN
        $osisString = $this->getBookName($this->bookNumber, $format);
        if ($this->chapter !== null && $part !== self::PART_BOOK) {
            // string: JHN.3
            $osisString .= '.' . $this->chapter;
        }
        if ($this->verse !== null && ($part === self::PART_ALL || $part === self::PART_VERSE)) {
            // string: JHN.3.16
            $osisString .= '.' . $this->verse;
        }
        if ($part === self::PART_ALL) {
            if ($this->bookNumberEnd !== null || $this->chapterEnd !== null || $this->verseEnd !== null) {
                $osisString .= '-';
                if ($this->bookNumberEnd !== null) {
                    $osisString .= $this->getBookName($this->bookNumberEnd, $format);
                } else {
                    $osisString .= $this->getBookName($this->bookNumber, $format);
                }
                if ($this->chapterEnd !== null) {
                    $osisString .= '.' . $this->chapterEnd;
                } else {
                    $osisString .= '.' . $this->chapter;
                }
                if ($this->verseEnd !== null) {
                    $osisString .= '.' . $this->verseEnd;
                }
            }
        }

        return $osisString;
    }
}
