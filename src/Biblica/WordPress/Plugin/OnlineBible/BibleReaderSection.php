<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Collator;
use Biblica\Bible\Translations\Entities\Audio;
use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Passage;
use Biblica\Bible\Translations\Entities\PassageFragments;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Util\LogUtilities;
use Biblica\WordPress\Plugin\Common\PageLink;
use Biblica\WordPress\Plugin\Common\BibleApiPage;
use Biblica\WordPress\Plugin\Common\DropDownItem;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;
use Biblica\WordPress\Plugin\OnlineBible\Exception\OnlineBibleException;

class BibleReaderSection extends BibleApiPage
{
    use LogUtilities;

    private int $sectionType;

    /** @var ?BibleReaderSection When two translations are being compared side-by-side this is the other control. */
    public ?BibleReaderSection $compareSection = null;

    /** @var ?PassageFragments Which fragments the control should render. */
    private ?PassageFragments $includeFragments = null;

    /** @var ?Translation This control's selected translation. */
    private ?Translation $translation = null;

    /** @var ?Passage Might be null if the chapter is not translated or the url parameters are wrong. */
    private ?Passage $passage = null;

    /** @var string[] */
    private array $fumsTokens = [];

    private ?BibleReaderUrlBuilderInterface $urlBuilder = null;

    public function __construct(
        WordPressPlugin $plugin,
        int $sectionType,
        int $includeFragments // should accept null
    ) {
        parent::__construct($plugin);

        $this->strings = $this->strings + [
                '/biblica/templates/rtb/navigation/nextChapterLabel' => __('Next chapter', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/nextBookLabel' => __('Next book', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/previousChapterLabel' => __('Previous chapter', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/previousBookLabel' => __('Previous book', 'biblica-online-bible'),
            ];

        $this->setSectionType($sectionType);
        $this->includeFragments = new PassageFragments($includeFragments);
    }

    /**
     * @return BibleReaderSectionDto
     * @throws OnlineBibleException
     */
    public function getTemplateData(): BibleReaderSectionDto
    {
        $data = new BibleReaderSectionDto();

        if ($this->getPassageTranslation() === null) {
            $data->show = false;
            return $data;
        } else {
            $data->show = true;
        }

        $data->translationId = $this->getPassageTranslation()->getId();
        $data->translationAbbreviation = $this->getPassageTranslation()->getAbbreviation();
        $data->translationName = $this->getPassageTranslation()->getName();
        $data->translationIsRightToLeft = $this->getPassageTranslation()->getLanguage()->isRightToLeft;
        $data->bibleWrapperClasses = $this->getBibleWrapperClasses();
        $data->audioBibleId = $this->getAudioBibleId();
        $data->book = $this->getPassageBook();
        $data->chapter = $this->getPassageChapter();
        $data->columns = $this->getColumns();

        $data->isPrimaryPassage = $this->sectionType === SectionType::PRIMARY;

        if ($this->passage !== null) {
            $data->passage = $this->getPassage();
            $data->nextBookLink = $this->getNextBookLink();
            $data->previousBookLink = $this->getPreviousBookLink();
            $data->nextChapterLink = $this->getNextChapterLink();
            $data->previousChapterLink = $this->getPreviousChapterLink();
            $passageService = $this->getPassageService();
            $data->filteredContent = $passageService->filterPassageContent($this->passage, $this->includeFragments);
        }

        if ($this->sectionType === SectionType::PRIMARY && $this->compareSection === null) {
            $data->showCloseButton = false;
            if ($this->getPlugin()->getOptions('general')->getOption('enablePolyglot') === true) {
                $data->showCompareTranslationsDropDown = true;
            } else {
                $data->showCompareTranslationsDropDown = false;
            }
        } else {
            $data->closeUrl = $this->getCloseUrl();
            $data->showCompareTranslationsDropDown = false;
            $data->showCloseButton = true;
        }

        $data->translationDropDownItems = $this->getTranslationDropDownItems();
        if ($data->showCompareTranslationsDropDown) {
            $data->compareTranslationDropDownItems = $this->getCompareTranslationDropDownItems();
        }

        if ($this->passage === null) {
            $data->showToolsAndPaging = false;
            $data->showMissingPassageText = true;
            $data->showPassage = false;
            $data->showFooterPaging = false;
        } else {
            $data->showToolsAndPaging = true;
            $data->showMissingPassageText = false;
            $data->showPassage = true;
            $data->showFooterPaging = true;

            if ($this->getAudio() === null) {
                $data->showAudioPlayer = false;
                $data->showNoAudioMessage = true;
            } else {
                $data->showAudioPlayer = true;
                $data->showNoAudioMessage = false;
            }

            if ($this->includeFragments->has(PassageFragments::FOOTNOTES)) {
                $data->showFootnotes = count($this->passage->footnotes) > 0;
            }

            if ($this->includeFragments->has(PassageFragments::CROSS_REFERENCES)) {
                $data->showCrossReferences = count($this->passage->crossReferences) > 0;
            }
        }

        $data->bibleApi = $this->getBibleApi();

        return $data;
    }

    public function setUrlBuilder(BibleReaderUrlBuilderInterface $urlBuilder): void
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @throws OnlineBibleException
     */
    private function getUrlBuilder(): BibleReaderUrlBuilderInterface
    {
        if ($this->urlBuilder === null) {
            throw new OnlineBibleException('Url builder is not set in ' . __CLASS__);
        }

        return $this->urlBuilder;
    }

    public function setIncludeFragments($fragments)
    {
        $this->includeFragments = new PassageFragments($fragments);
    }

    public function getSectionType(): int
    {
        return $this->sectionType;
    }

    public function setSectionType(int $sectionType): void
    {
        $this->sectionType = $sectionType;
    }

    public function getPassageTranslation(): ?Translation
    {
        return $this->passage?->translation;
    }

    public function getPassage(): ?Passage
    {
        return $this->passage;
    }

    public function setPassage(?Passage $passage): void
    {
        $this->passage = $passage;
    }

    public function getCompareTranslation(): ?Translation
    {
        return $this->compareSection?->getPassageTranslation();
    }

    public function getPassageBook(): ?Book
    {
        return $this->passage?->getBook();
    }

    public function getPassageChapter(): ?Chapter
    {
        return $this->passage?->getChapter();
    }

    public function getAudio(): ?Audio
    {
        return $this->passage->audio[0] ?? null;
    }

    protected function getColumns(): int
    {
        if ($this->compareSection === null) {
            return 12;
        }

        return 6;
    }

    /**
     * @return DropDownItem[]
     * @throws OnlineBibleException
     */
    public function getTranslationDropDownItems(): array
    {
        $allTranslations = $this->getTranslationService()->getActiveTranslations();
        $translationDropDownItems = [];
        foreach ($allTranslations as $translation) {
            if ($this->getCompareTranslation() === null || $translation->getId() !== $this->getCompareTranslation()->getId()) {
                $translationUrl = $this->changeTranslationUrl($translation);
                $translationDescription = mb_convert_case(
                    $translation->getLanguage()->nameLocal,
                    MB_CASE_TITLE
                ) . ' | ' . $translation->getName() . ' (' . $translation->getAbbreviation() . ')';
                $translationDropDownItems[] = new DropDownItem($translationDescription, $translationUrl);
            }
        }

        $collator = new Collator(get_locale());
        usort($translationDropDownItems, function (DropDownItem $item1, DropDownItem $item2) use ($collator) {
            return $collator->compare($item1->text, $item2->text);
        });

        return $translationDropDownItems;
    }

    /**
     * @return DropDownItem[]
     * @throws OnlineBibleException
     */
    public function getCompareTranslationDropDownItems(): array
    {
        $activeTranslations = $this->getTranslationService()->getActiveTranslations();
        $translationDropDownItems = [];
        foreach ($activeTranslations as $translation) {
            if ($translation->getId() !== $this->getPassageTranslation()->getId()) {
                $translationUrl = $this->compareTranslationUrl($translation);
                $translationDescription = mb_convert_case(
                    $translation->getLanguage()->nameLocal,
                    MB_CASE_TITLE
                ) . ' | ' . $translation->getName() . ' (' . $translation->getAbbreviation() . ')';
                $translationDropDownItems[] = new DropDownItem($translationDescription, $translationUrl);
            }
        }

        $collator = new Collator(get_locale());
        usort($translationDropDownItems, function (DropDownItem $item1, DropDownItem $item2) use ($collator) {
            return $collator->compare($item1->text, $item2->text);
        });

        return $translationDropDownItems;
    }

    public function getNextBook(): Book
    {
        $maxIndex = count($this->getPassageTranslation()->books) - 1;
        $nextIndex = array_search($this->getPassageBook(), $this->getPassageTranslation()->books, true) + 1;

        if ($nextIndex > $maxIndex) {
            $nextIndex = 0;
        }

        return $this->getPassageTranslation()->books[$nextIndex];
    }

    public function getPreviousBook(): Book
    {
        $maxIndex = count($this->getPassageTranslation()->books) - 1;
        $previousIndex = array_search($this->getPassageBook(), $this->getPassageTranslation()->books) - 1;

        if ($previousIndex < 0) {
            $previousIndex = $maxIndex;
        }

        return $this->getPassageTranslation()->books[$previousIndex];
    }

    /**
     * @throws OnlineBibleException
     */
    protected function getCloseUrl(): string
    {
        if ($this->sectionType === SectionType::PRIMARY) {
            if ($this->passage === null && $this->compareSection !== null) {
                return $this->getUrlBuilder()->getUrl(
                    $this->compareSection->getPassageTranslation(),
                    $this->compareSection->getPassageTranslation()->books[0],
                    $this->compareSection->getPassageTranslation()->books[0]->chapters[0]
                );
            }

            $book = null;
            foreach ($this->compareSection->getPassageTranslation()->books as $thisBook) {
                // TODO: search needs to be case insensitive?
                if (in_array($this->passage->osis, $thisBook->getOsises(), true)) {
                    $book = $thisBook;
                    break;
                }
            }

            if ($book === null) {
                $book = $this->compareSection->getPassageTranslation()->books[0];
            }

            $chapter = null;
            foreach ($book->chapters as $thisChapter) {
                // TODO: search needs to be case insensitive?
                if (in_array($this->passage->osis, $thisChapter->getOsises(), true)) {
                    $chapter = $thisChapter;
                }
            }

            if ($chapter === null) {
                $chapter = $book->chapters[0];
            }

            return $this->getUrlBuilder()->getUrl(
                $this->compareSection->getPassageTranslation(),
                $book,
                $chapter
            );
        } else {
            return $this->getUrlBuilder()->getUrl(
                $this->compareSection->getPassageTranslation(),
                $this->compareSection->getPassageBook(),
                $this->compareSection->getPassageChapter()
            );
        }
    }

    /**
     * @throws OnlineBibleException
     */
    public function getNextBookLink(): PageLink
    {
        if ($this->getSectionType() === SectionType::SECONDARY) {
            $leftSection = $this->compareSection;
            $rightSection = $this;
        } else {
            $leftSection = $this;
            $rightSection = $this->compareSection;
        }
        $leftTranslation = $leftSection->getPassageTranslation();
        $rightTranslation = $rightSection?->getPassageTranslation();
        $leftBook = $leftSection->getNextBook();
        $thisBook = $this->getNextBook();

        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $leftTranslation,
            $leftBook,
            $leftBook->chapters[0],
            $rightTranslation
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/nextBookLabel'] . ': ' . $thisBook->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     */
    public function getPreviousBookLink(): PageLink
    {
        if ($this->getSectionType() === SectionType::SECONDARY) {
            $leftSection = $this->compareSection;
            $rightSection = $this;
        } else {
            $leftSection = $this;
            $rightSection = $this->compareSection;
        }
        $leftTranslation = $leftSection->getPassageTranslation();
        $rightTranslation = $rightSection?->getPassageTranslation();
        $leftBook = $leftSection->getPreviousBook();
        $thisBook = $this->getPreviousBook();

        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $leftTranslation,
            $leftBook,
            $leftBook->chapters[0],
            $rightTranslation
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/previousBookLabel'] .
            ': ' . $thisBook->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     */
    public function getNextChapterLink(): PageLink
    {
        if ($this->getSectionType() === SectionType::SECONDARY) {
            $leftSection = $this->compareSection;
            $rightSection = $this;
        } else {
            $leftSection = $this;
            $rightSection = $this->compareSection;
        }
        $leftTranslation = $leftSection->getPassageTranslation();
        $rightTranslation = $rightSection?->getPassageTranslation();

        $maxIndex = count($leftSection->getPassageBook()->chapters) - 1;
        $nextIndex = array_search($leftSection->getPassageChapter(), $leftSection->getPassageBook()->chapters, true) + 1;

        if ($nextIndex > $maxIndex) {
            $leftBook = $leftSection->getNextBook();
            $leftChapter = $leftSection->getNextBook()->chapters[0];
            $thisBook = $this->getNextBook();
            $thisChapter = $this->getNextBook()->chapters[0];
        } else {
            $leftBook = $leftSection->getPassageBook();
            $leftChapter = $leftSection->getPassageBook()->chapters[$nextIndex];
            $thisBook = $this->getPassageBook();
            $thisChapter = $this->getPassageBook()->chapters[$nextIndex];
        }

        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $leftTranslation,
            $leftBook,
            $leftChapter,
            $rightTranslation
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/nextChapterLabel'] .
            ': ' . $thisBook->name . ' ' . $thisChapter->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     */
    public function getPreviousChapterLink(): PageLink
    {
        if ($this->getSectionType() === SectionType::SECONDARY) {
            $leftSection = $this->compareSection;
            $rightSection = $this;
        } else {
            $leftSection = $this;
            $rightSection = $this->compareSection;
        }
        $leftTranslation = $leftSection->getPassageTranslation();
        $rightTranslation = $rightSection?->getPassageTranslation();

        $previousIndex = array_search($leftSection->getPassageChapter(), $leftSection->getPassageBook()->chapters) - 1;

        if ($previousIndex < 0) {
            $lastIndex = count($leftSection->getPreviousBook()->chapters) - 1;
            $leftBook = $leftSection->getPreviousBook();
            $leftChapter = $leftSection->getPreviousBook()->chapters[$lastIndex];
            $thisBook = $this->getPreviousBook();
            $thisChapter = $this->getPreviousBook()->chapters[$lastIndex];
        } else {
            $leftBook = $leftSection->getPassageBook();
            $leftChapter = $leftSection->getPassageBook()->chapters[$previousIndex];
            $thisBook = $this->getPassageBook();
            $thisChapter = $this->getPassageBook()->chapters[$previousIndex];
        }

        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $leftTranslation,
            $leftBook,
            $leftChapter,
            $rightTranslation
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/previousChapterLabel'] .
            ': ' . $thisBook->name . ' ' . $thisChapter->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     */
    protected function changeTranslationUrl(Translation $translation): string
    {
        if ($this->sectionType === SectionType::PRIMARY) {
            // Book
            $book = null;
            // Chapter
            $chapter = null;

            if ($this->passage === null) {
                $book = $translation->books[0];
                $chapter = $book->chapters[0];
            } else {
                foreach ($translation->books as $thisBook) {
                    // TODO: search needs to be case insensitive?
                    if (in_array($this->passage->osis, $thisBook->getOsises(), true)) {
                        $book = $thisBook;
                        break;
                    }
                }

                if ($book === null) {
                    $book = $translation->books[0];
                }

                foreach ($book->chapters as $thisChapter) {
                    // TODO: search needs to be case insensitive?
                    if (in_array($this->passage->osis, $thisChapter->getOsises(), true)) {
                        $chapter = $thisChapter;
                        break;
                    }
                }

                if ($chapter === null) {
                    $chapter = $book->chapters[0];
                }
            }

            return $this->getUrlBuilder()->getUrl(
                $translation,
                $book,
                $chapter,
                $this->getCompareTranslation()
            );
        } else {
            return $this->getUrlBuilder()->getUrl(
                $this->compareSection->getPassageTranslation(),
                $this->compareSection->getPassageBook(),
                $this->compareSection->getPassageChapter(),
                $translation
            );
        }
    }

    /**
     * @throws OnlineBibleException
     */
    protected function compareTranslationUrl(Translation $translation): string
    {
        return $this->getUrlBuilder()->getUrl(
            $this->getPassageTranslation(),
            $this->getPassageBook(),
            $this->getPassageChapter(),
            $translation
        );
    }

    public function getBibleWrapperClasses(): string
    {
        return $this->getPassageTranslation()->getStyleSheet()->wrapperClasses;
    }

    public function getAudioBibleId(): ?string
    {
        $audioBible = $this->getPassageTranslation()->getAudioBible();

        return $audioBible?->id;
    }

    public function getBibleApi(): string
    {
        return 'ApiDotBibleApi';
    }

    public function getFumsTokens(): array
    {
        $passage = $this->getPassage();
        $token = $passage?->apiTrackingToken;

        return $token === null ? [] : [$token];
    }
}
