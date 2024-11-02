<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Exception;
use Biblica\Bible\Translations\Entities\BibleReference;
use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Passage;
use Biblica\Bible\Translations\Entities\PassageFragments;
use Biblica\Bible\Translations\Entities\StyleSheet;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Util\LogUtilities;
use Biblica\Util\TwigManager;
use Biblica\WordPress\Plugin\OnlineBible\Exception\OnlineBibleException;
use Biblica\WordPress\Plugin\Common\BibleApiPage;
use Biblica\WordPress\Plugin\Common\DropDownItem;
use Biblica\WordPress\Plugin\Common\HttpParameters;
use Biblica\WordPress\Plugin\Common\PageLink;
use Biblica\WordPress\Plugin\Common\SeoPageInterface;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;
use Psr\Log\LogLevel;

class BibleReader extends BibleApiPage implements SeoPageInterface
{
    use LogUtilities;

    public ?BibleReaderSection $primarySection = null;
    public ?BibleReaderSection $secondarySection = null;

    private ?Translation $readerTranslation = null;
    private ?Translation $compareTranslation = null;
    private ?Book $book = null;
    private ?Chapter $chapter = null;

    private array $passages = [];
    private array $fumsTokens = [];

    public function __construct(WordPressPlugin $plugin, bool $render = false)
    {
        parent::__construct($plugin);

        $this->strings = $this->strings + [
                '/bible-reader/not-available' => __('The Bible reader is not available right now. Please try again later.', 'biblica-online-bible'),
                '/bible-reader/compare-with-label' => __('Compare with…', 'biblica-online-bible'),
                '/biblica/templates/rtb/hideLabel' => __('Hide this version', 'biblica-online-bible'),
                '/biblica/templates/rtb/tools/listenLabel' => __('Listen to this text', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/nextChapterLabel' => __('Next chapter', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/nextBookLabel' => __('Next book', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/previousChapterLabel' => __('Previous chapter', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/previousBookLabel' => __('Previous book', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/selectBookLabel' => __('Select book', 'biblica-online-bible'),
                '/biblica/templates/rtb/navigation/selectChapterLabel' => __('Select chapter', 'biblica-online-bible'),
                '/biblica/templates/rtb/footnotesHeading' => __('Footnotes', 'biblica-online-bible'),
                '/biblica/templates/rtb/crossReferencesHeading' => __('Cross-references', 'biblica-online-bible'),
                '/biblica/templates/rtb/search/queryLabel' => __('Search the Bible', 'biblica-online-bible'),
                '/biblica/templates/rtb/search/queryPlaceholder' => __('Search the %s Bible', 'biblica-online-bible'),
                '/biblica/templates/rtb/search/buttonLabel' => __('Search', 'biblica-online-bible'),
                '/biblica/templates/rtb/bible/swe' => __('Bibeln', 'biblica-online-bible'),
                '/biblica/templates/rtb/noAudioText' => __('There is no audio yet for this translation.', 'biblica-online-bible'),
                '/biblica/templates/rtb/scholarNotesHeading' => __('Scholar Notes', 'biblica-online-bible'),
                // translators: 1: the book of the Bible where the verse is from 2: chapter number of the book where the verse is from
                '/biblica/templates/rtb/passageReadChapterLabelFormat' => __('Read More of %1$s %2$s', 'biblica-online-bible'),

                '/bible/online-bible/missing-passage-text' => __('This chapter is not currently available. Please try again later.', 'biblica-online-bible'),
                '/bible/online-bible/not-translated-text' => __('This chapter is not available yet in this translation.', 'biblica-online-bible'),
                '/bible/online-bible/meta-description' => __('Read the Bible online, in The New International Version (NIV), or a variety of other languages and translations.', 'biblica-online-bible'),
                '/bible/online-bible/parallel/heading' => __('Parallel Bible', 'biblica-online-bible'),
                // translators: 1: abbreviation of the first side by side translation (e.g. NIV), 2: abbreviation of the second side by side translation (e.g. KJV)
                '/bible/online-bible/parallel/meta-description-short' => __('Read and compare the %1$s and %2$s Bible translations side by side with our %1$s/%2$s parallel Bible.', 'biblica-online-bible'),
                '/reader/disabled/unverified-bible-api-key' => __('The Bible reader is disabled. In order to use the Bible reader you must enter a valid key in the Bible API Key field in settings.', 'biblica-online-bible'),
                '/reader/disabled/unavailable' => __('The Bible reader is not available right now. Please try again later.', 'biblica-online-bible'),
            ];

        if (!$this->showOsis()) {
            $this->primarySection = $this->createBibleReaderSection(SectionType::PRIMARY, $this->getReaderTranslation());
            $this->secondarySection = $this->createBibleReaderSection(SectionType::SECONDARY, $this->getCompareTranslation());
            if ($this->getCompareTranslation() !== null) {
                $this->primarySection->compareSection = $this->secondarySection;
                $this->secondarySection->compareSection = $this->primarySection;
            }
        }

        if ($render) {
            echo $this->render();
        }
    }

    public function render(): string
    {
        try {
            $data = $this->getTemplateData();

            add_filter('wp_headers', function ($headers) {
                $headers['Cache-Control'] = 'must-revalidate, max-age=14400';

                return $headers;
            });

            $twigContext = [
                'data' => $data,
                'strings' => $this->getStrings(),
            ];

            $twigTemplate = 'bible-reader.twig';
            $templateLocation = $this->getPlugin()->getPluginPath() . 'templates';
            $twig = TwigManager::createEnvironment($templateLocation);

            $html = $twig->render($twigTemplate, $twigContext);
        } catch (Exception $e) {
            $this->logException($e);
            $html = '<div class="disabled-message">' . $this->getString('/reader/disabled/unavailable') . '</div>';
        }

        return $html;
    }

    /**
     * @return BibleReaderDto
     * @throws Exception
     */
    public function getTemplateData(): BibleReaderDto
    {
        $data = new BibleReaderDto();

        $data->disabledMessage = $this->getDisabledMessage();
        if ($data->disabledMessage !== null) {
            return $data;
        }

        $data->missingPassageText = $this->strings['/bible/online-bible/not-translated-text'];

        $data->book = $this->getReaderBook();
        $data->chapter = $this->getReaderChapter();
        $data->searchTranslationId = $this->getReaderTranslation()->getId();
        $data->audioBibleId = $this->getAudioBibleId();

        $data->bibleWrapperClasses = $this->getBibleWrapperClasses();

        $data->heading = $this->getHeading();
        $data->nextBookLink = $this->getNextBookUrl();
        $data->previousBookLink = $this->getPreviousBookUrl();
        $data->nextChapterLink = $this->getNextChapterUrl();
        $data->previousChapterLink = $this->getPreviousChapterUrl();
        $data->bookDropDownItems = $this->getBookDropDownItems();
        $data->chapterDropDownItems = $this->getChapterDropDownItems();
        $data->bibleSearchUrl = $this->getBibleSearchUrl();
        $data->searchPlaceholder = $this->getSearchPlaceholder();

        $data->ipAddress = sanitize_text_field($_SERVER['SERVER_ADDR']);

        $data->showSearchForm = $this->showSearchForm();
        $data->showOsis = $this->showOsis();
        $data->showMissingPassageText = false;
        $data->passages = [];

        if ($this->showOsis()) {
            $data->passages = $this->getPassages();
            foreach ($data->passages as $passage) {
                $passage->osisContent = $this->getPassageService()->filterPassageContent(
                    $passage,
                    new PassageFragments(PassageFragments::HEADINGS | PassageFragments::FOOTNOTES)
                );
                $data->chapterLinks[$passage->osis] = $this->getChapterLinks($passage);
                $this->fumsTokens[] = $passage->apiTrackingToken;
            }
            $data->showMissingPassageText = count($this->getPassages()) < 1;
        } else {
            $data->primarySectionData = $this->primarySection->getTemplateData();
            $data->secondarySectionData = $this->secondarySection->getTemplateData();
        }
        $data->fumsTokens = $this->getFumsTokens();

        return $data;
    }

    /**
     * @throws Exception
     */
    protected function getDisabledMessage(): ?string
    {
        $message = null;

        $options = $this->getPlugin()->getOptions('general');
        $bibleApiKey = $options->getOption('bibleApiKey');
        $verifiedBibleApiKey = $options->getOption('verifiedBibleApiKey');
        if ($bibleApiKey === '' || $bibleApiKey !== $verifiedBibleApiKey) {
            $message = $this->getString('/reader/disabled/unverified-bible-api-key');
        }

        if ($this->showOsis()) {
            return $message;
        }

        $redirectToDefault = false;
        if (
            $this->getReaderTranslation() === null ||
            $this->getReaderBook() === null ||
            $this->getReaderChapter() === null
        ) {
            $redirectToDefault = true;
        }

        if ($redirectToDefault === true) {
            if (!$this->redirectToDefault()) {
                $message = $this->getString('/reader/disabled/unavailable');
            }
        }

        return $message;
    }

    /**
     * @throws OnlineBibleException
     */
    protected function redirectToDefault(): bool
    {
        if (is_admin()) {
            return false;
        }

        $defaultTranslation = $this->getDefaultTranslation();
        if ($defaultTranslation === null) {
            // Cannot redirect to the default translation since it is not available
            return false;
        }

        $defaultTranslationId = $defaultTranslation->getId();
        $defaultBookUrlSegment = $defaultTranslation->books[0]->urlSegment;
        $defaultChapterName = $defaultTranslation->books[0]->chapters[0]->name;

        $currentTranslationId = $this->getTranslationId();
        $currentBookUrlSegment = $this->getBookUrlSegment();
        $currentChapterName = $this->getChapterName();

        if (
            $defaultTranslationId === $currentTranslationId &&
            $defaultBookUrlSegment === $currentBookUrlSegment &&
            $defaultChapterName === $currentChapterName
        ) {
            // Avoid a redirect loop
            return false;
        }

        $url = $this->getUrlBuilder()->getUrl($defaultTranslation);
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Location: ' . $url, true, 302);
        exit;
    }

    private function showSearchForm(): bool
    {
        $options = $this->getPlugin()->getOptions('general');
        $showReaderSearchForm = $options->getOption('showReaderSearchForm');
        $bibleSearchPageId = $options->getOption('bibleSearchPageId');
        if ($showReaderSearchForm === false || $bibleSearchPageId === 0) {
            return false;
        }

        return true;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    private function createBibleReaderSection(int $sectionType, ?Translation $translation): BibleReaderSection
    {
        $bibleReaderSection = new BibleReaderSection(
            $this->getPlugin(),
            $sectionType,
            PassageFragments::VERSE_NUMBERS |
            PassageFragments::FOOTNOTES |
            PassageFragments::HEADINGS
        );
        $bibleReaderSection->setUrlBuilder($this->getUrlBuilder());

        if ($translation !== null) {
            $reference = new BibleReference($this->getReaderChapter()->osis);
            $osis = $reference->toOsisString($translation->getBibleReferenceFormat());
            $passage = $this->getPassageService()->getPassages(
                $osis,
                [$translation->getId()]
            )[0] ?? null;
            $bibleReaderSection->setPassage($passage);
        }

        return $bibleReaderSection;
    }

    /**
     * @throws OnlineBibleException
     */
    private function getUrlBuilder(): BibleReaderUrlBuilderInterface
    {
        return $this->getPlugin()->getUrlBuilder();
    }

    public function getReaderTranslation(): ?Translation
    {
        if ($this->showOsis()) {
            $osis = '';
            $translationId = '';

            $this->parseOsis(
                $this->getOsis(),
                $osis,
                $translationId
            );

            $this->readerTranslation = $this->getTranslationService()->getTranslation($translationId);
        }

        if ($this->readerTranslation === null && $this->getTranslationId() === null) {
            $this->readerTranslation = $this->getDefaultTranslation();
        } elseif ($this->readerTranslation === null) {
            $this->readerTranslation = $this->getTranslation();
        }

        return $this->readerTranslation;
    }

    /**
     * @throws Exception
     */
    public function getCompareTranslation(): ?Translation
    {
        if ($this->compareTranslation === null && $this->getCompareTranslationId() !== null) {
            $this->compareTranslation = $this->getTranslationService()->getTranslation(
                $this->getCompareTranslationId()
            );
        }

        return $this->compareTranslation;
    }

    /**
     * @throws Exception
     */
    public function getBook(): ?Book
    {
        if ($this->book === null) {
            $bookUrlSegment = $this->getBookUrlSegment();
            if ($bookUrlSegment !== null && $bookUrlSegment !== '') {
                $this->book = $this->getReaderTranslation()->booksByUrlSegment[mb_strtolower($bookUrlSegment)] ?? null;
            }
        }

        return $this->book;
    }

    /**
     * @throws Exception
     */
    public function getReaderBook(): ?Book
    {
        if ($this->book === null) {
            if ($this->showOsis()) {
                $this->book = $this->getReaderTranslation()->books[0];
            } else {
                $this->book = $this->getBook();
            }

            if ($this->book === null) {
                $this->book = $this->getReaderTranslation()->books[0];
            }
        }

        return $this->book;
    }

    /**
     * @throws Exception
     */
    public function getChapter(): ?Chapter
    {
        if ($this->chapter === null) {
            if ($this->showOsis()) {
                $this->chapter = $this->getBook()->chapters[0] ?? null;
            }

            $chapterName = $this->getChapterName();
            if ($chapterName !== null && $chapterName !== '') {
                $this->chapter = $this->getBook()->chaptersByName[$chapterName] ?? null;
            }
        }

        return $this->chapter;
    }

    /**
     * @throws Exception
     */
    public function getReaderChapter(): ?Chapter
    {
        if ($this->chapter === null) {
            if ($this->showOsis()) {
                $this->chapter = $this->getReaderBook()->chapters[0];
            } else {
                $this->chapter = $this->getChapter();
            }

            if ($this->chapter === null) {
                $this->chapter = $this->getReaderTranslation()->books[0]->chapters[0];
            }
        }

        return $this->chapter;
    }

    /**
     * @return Passage[]
     */
    public function getPassages(): array
    {
        if (!$this->showOsis()) {
            return [];
        }

        $osis = '';
        $translationId = '';

        $this->parseOsis(
            $this->getOsis(),
            $osis,
            $translationId
        );

        try {
            $this->passages = $this->getPassageService()->getPassages($osis, [$translationId]);
        } catch (Exception $e) {
            $this->log(LogLevel::ERROR, $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->passages = [];
        }

        return $this->passages;
    }

    /**
     * @throws Exception
     */
    public function getNextBook(): Book
    {
        $maxIndex = count($this->getReaderTranslation()->books) - 1;
        $nextIndex = array_search($this->getReaderBook(), $this->getReaderTranslation()->books, true) + 1;

        if ($nextIndex > $maxIndex) {
            $nextIndex = 0;
        }

        return $this->getReaderTranslation()->books[$nextIndex];
    }

    /**
     * @throws Exception
     */
    public function getPreviousBook(): Book
    {
        $maxIndex = count($this->getReaderTranslation()->books) - 1;
        $previousIndex = array_search($this->getReaderBook(), $this->getReaderTranslation()->books) - 1;

        if ($previousIndex < 0) {
            $previousIndex = $maxIndex;
        }

        return $this->getReaderTranslation()->books[$previousIndex];
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getNextBookUrl(): PageLink
    {
        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $this->getReaderTranslation(),
            $this->getNextBook(),
            $this->getNextBook()->chapters[0],
            $this->getCompareTranslation()
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/nextBookLabel'] . ': ' . $this->getNextBook()->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getPreviousBookUrl(): PageLink
    {
        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $this->getReaderTranslation(),
            $this->getPreviousBook(),
            $this->getPreviousBook()->chapters[0],
            $this->getCompareTranslation()
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/previousBookLabel'] . ': ' . $this->getPreviousBook()->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getNextChapterUrl(): PageLink
    {
        $maxIndex = count($this->getReaderBook()->chapters) - 1;
        $nextIndex = array_search($this->getReaderChapter(), $this->getReaderBook()->chapters, true) + 1;

        if ($nextIndex > $maxIndex) {
            $book = $this->getNextBook();
            $chapter = $this->getNextBook()->chapters[0];
        } else {
            $book = $this->getReaderBook();
            $chapter = $this->getReaderBook()->chapters[$nextIndex];
        }

        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $this->getReaderTranslation(),
            $book,
            $chapter,
            $this->getCompareTranslation()
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/nextChapterLabel'] . ': ' .
            $book->name . ' ' . $chapter->name;

        return $link;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getPreviousChapterUrl(): PageLink
    {
        $previousIndex = array_search($this->getReaderChapter(), $this->getReaderBook()->chapters) - 1;

        if ($previousIndex < 0) {
            $lastIndex = count($this->getPreviousBook()->chapters) - 1;
            $book = $this->getPreviousBook();
            $chapter = $this->getPreviousBook()->chapters[$lastIndex];
        } else {
            $book = $this->getReaderBook();
            $chapter = $this->getReaderBook()->chapters[$previousIndex];
        }

        $link = new PageLink();
        $link->url = $this->getUrlBuilder()->getUrl(
            $this->getReaderTranslation(),
            $book,
            $chapter,
            $this->getCompareTranslation()
        );
        $link->text = $this->strings['/biblica/templates/rtb/navigation/previousChapterLabel'] . ': ' .
            $book->name . ' ' . $chapter->name;

        return $link;
    }

    /**
     * @param Book|string $book A book object or a book name
     * @return string
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function changeBookUrl(Book|string $book): string
    {
        $newBook = null;
        // if book is a string it should be the book name
        if (gettype($book) === 'string') {
            foreach ($this->getReaderTranslation()->books as $thisBook) {
                if (mb_strtolower($thisBook->name) === mb_strtolower($book)) {
                    $newBook = $thisBook;
                    break;
                }
            }

            // TODO: use current book if new book is not found?
            if ($newBook === null) {
                $newBook = $this->getReaderBook();
            }
        } else {
            $newBook = $book;
        }

        return $this->getUrlBuilder()->getUrl(
            $this->getReaderTranslation(),
            $newBook,
            $newBook->chapters[0],
            $this->getCompareTranslation()
        );
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function changeChapterUrl(Chapter $chapter): string
    {
        return $this->getUrlBuilder()->getUrl(
            $this->getReaderTranslation(),
            $this->getReaderBook(),
            $chapter,
            $this->getCompareTranslation()
        );
    }

    /**
     * @param Passage $passage
     * @return string
     * @throws Exception
     */
    public function getChapterLinks(Passage $passage): string
    {
        $links = '';
        $hash = [];
        $labelFormat = $this->strings['/biblica/templates/rtb/passageReadChapterLabelFormat'];

        $osisChapters = [];
        $osisReferences = mb_split(',', $passage->osis);
        foreach ($osisReferences as $osisReference) {
            $reference = new BibleReference($osisReference);
            $osisChapters[] = $reference->toOsisString(
                $passage->translation->getBibleReferenceFormat(),
                BibleReference::PART_CHAPTER
            );
        }

        foreach ($osisChapters as $osis) {
            $book = null;
            foreach ($passage->translation->books as $thisBook) {
                if (array_search($osis, $thisBook->getOsises(), true)) {
                    $book = $thisBook;
                    break;
                }
            }

            if ($book === null || in_array($book, $hash, true)) {
                continue;
            }

            $hash[] = $book;

            $chapter = null;
            foreach ($book->chapters as $thisChapter) {
                if (in_array($osis, $thisChapter->getOsises(), true)) {
                    $chapter = $thisChapter;
                    break;
                }
            }

            $url = $this->getUrlBuilder()->getUrl(
                $passage->translation,
                $book,
                $chapter
            );

            $links .= sprintf(
                '<a class="btn btn-default" href="%s">%s</a> ',
                $url,
                sprintf($labelFormat, $book->name, $chapter->name)
            );
        }

        return trim($links);
    }

    public function showOsis(): bool
    {
        $osis = $this->getOsis();

        return $osis !== null && $osis !== '';
    }

    /**
     * @throws Exception
     */
    public function isParallelMainPage(): bool
    {
        // example url: /bible/niv/kjv/
        if (
            $this->getTranslationId() !== null &&
            $this->getBookUrlSegment() === null &&
            $this->getChapterName() === null &&
            $this->getCompareTranslationId() !== null
        ) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function isMainPage(): bool
    {
        if (
            // example url: /bible/
            $this->getTranslationId() === null &&
            $this->getBookUrlSegment() === null &&
            $this->getChapterName() === null &&
            $this->getCompareTranslationId() === null &&
            sanitize_text_field($_SERVER['QUERY_STRING']) === ''
        ) {
            return true;
        }

        return false;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getBookDropDownItems(): array
    {
        $bookDropDownItems = [];
        foreach ($this->getReaderTranslation()->books as $book) {
            $bookDropDownItems[] = new DropDownItem($book->name, $this->changeBookUrl($book));
        }

        return $bookDropDownItems;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getChapterDropDownItems(): array
    {
        $chapterDropDownItems = [];
        foreach ($this->getReaderBook()->chapters as $chapter) {
            $chapterDropDownItems[] = new DropDownItem($chapter->name, $this->changeChapterUrl($chapter));
        }

        return $chapterDropDownItems;
    }

    public function getBibleSearchUrl()
    {
        $options = $this->getPlugin()->getOptions('general');

        return get_permalink($options->getOption('bibleSearchPageId'));
    }

    /**
     * @throws Exception
     */
    public function getTitle(): string
    {
        if ($this->isMainPage()) {
            $title = '';
        } else {
            $title = $this->getHeading();
        }

        return $title;
    }

    public function getHeading(): string
    {
        $heading = apply_filters('biblica_ob_reader_heading', '', $this);

        return is_string($heading) ? $heading : '';
    }

    /**
     * @throws Exception
     */
    public function getDescription(): string
    {
        if ($this->isMainPage()) {
            // example url: /bible/
            $description = $this->getStrings()['/bible/online-bible/meta-description'];
        } elseif ($this->isParallelMainPage()) {
            $description = sprintf(
                $this->getStrings()['/bible/online-bible/parallel/meta-description-short'],
                $this->getReaderTranslation()->getAbbreviation(),
                $this->getCompareTranslation()->getAbbreviation()
            );
        } else {
            if ($this->showOsis()) {
                $passage = $this->getPassages()[0];
            } else {
                $passage = $this->primarySection->getPassage();
            }
            if ($passage !== null) {
                $passageService = $this->getPassageService();
                $verses = $passageService->filterPassageContent($passage, new PassageFragments(PassageFragments::NONE));
                $description = trim($this->cleanContent(strip_tags($verses)));
            } else {
                $description = '';
            }
        }

        return $description;
    }

    /**
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getCanonicalUrl(): string
    {
        if ($this->isMainPage()) {
            // example url: /bible/
            $url = get_permalink();
        } elseif ($this->showOsis()) {
            // example url: /bible/?osisid=71c6eab17ae5b667-01:gen.1.1
            $url = $this->getUrlBuilder()->getOsisUrl(
                $this->getReaderTranslation(),
                $this->getPassages()
            );
        } elseif ($this->getCompareTranslation() === null) {
            // example url: /bible/niv/
            // example url: /bible/niv/genesis/1/
            $url = $this->getUrlBuilder()->getUrl(
                $this->getReaderTranslation(),
                $this->getReaderBook(),
                $this->getReaderChapter()
            );
        } elseif ($this->isParallelMainPage()) {
            // example url: /bible/niv/kjv/
            $url = get_permalink() . sprintf(
                '%1$s/%2$s/',
                $this->getReaderTranslation()->getId(),
                $this->getCompareTranslation()->getId()
            );
        } else {
            // example url: /bible/niv/genesis/1/kjv/
            $url = $this->getUrlBuilder()->getUrl(
                $this->getReaderTranslation(),
                $this->getReaderBook(),
                $this->getReaderChapter(),
                $this->getCompareTranslation()
            );
        }

        return $url;
    }

    public function cleanContent(string $content): string
    {
        // remove non-breaking space entities
        $content = preg_replace('~(&nbsp;)+~ui', ' ', $content);

        // remove non-breaking space entities
        $content = preg_replace('~(&#039;)+~ui', ' ', $content);

        return $content;
    }

    /**
     * @throws Exception
     */
    public function getDefaultStyleSheet(): ?StyleSheet
    {
        return $this->getReaderTranslation()->getStyleSheet();
    }

    /**
     * @throws Exception
     */
    public function getBibleWrapperClasses(): string
    {
        return $this->getDefaultStyleSheet()->wrapperClasses;
    }

    /**
     * @throws Exception
     */
    public function getAudioBibleId(): ?string
    {
        $audioBible = $this->getReaderTranslation()->getAudioBible();

        return $audioBible?->id;
    }

    public function getFumsTokens(): array
    {
        $allFumsTokens = $this->fumsTokens;
        if ($this->primarySection !== null) {
            $allFumsTokens = array_merge($allFumsTokens, $this->primarySection->getFumsTokens());
        }
        if ($this->secondarySection !== null) {
            $allFumsTokens = array_merge($allFumsTokens, $this->secondarySection->getFumsTokens());
        }
        return $allFumsTokens;
    }

    /**
     * @throws Exception
     */
    public function getSearchPlaceholder(): string
    {
        return sprintf(
            $this->strings['/biblica/templates/rtb/search/queryPlaceholder'],
            $this->getReaderTranslation()->getAbbreviation()
        );
    }
}
