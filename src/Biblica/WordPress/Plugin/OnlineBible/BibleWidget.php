<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Collator;
use Exception;
use Biblica\Bible\Translations\Entities\Book;
use Biblica\Bible\Translations\Entities\Chapter;
use Biblica\Bible\Translations\Entities\Translation;
use Biblica\Util\LogUtilities;
use Biblica\Util\TwigManager;
use Biblica\WordPress\Plugin\OnlineBible\Exception\OnlineBibleException;
use Biblica\WordPress\Plugin\Common\BibleApiPage;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;

class BibleWidget extends BibleApiPage
{
    use LogUtilities;

    private ?array $availableTranslations = null;
    private ?Translation $selectedTranslation = null;
    private ?Book $selectedBook = null;
    private ?Chapter $selectedChapter = null;
    private array $shortCodeAttributes;

    public function __construct(WordPressPlugin $plugin, array $shortCodeAttributes = [])
    {
        parent::__construct($plugin);

        $this->shortCodeAttributes = shortcode_atts([
            'read' => 'true',
            'search' => 'true'
        ], $shortCodeAttributes);

        $this->strings = $this->strings + [
            '/biblica/templates/rtb/widget/translationSelectLabel' => __('Choose Bible version', 'biblica-online-bible'),
            '/biblica/templates/rtb/widget/bookSelectLabel' => __('Book', 'biblica-online-bible'),
            '/biblica/templates/rtb/widget/chapterSelectLabel' => __('Chapter', 'biblica-online-bible'),
            '/biblica/templates/rtb/widget/submitButton' => __('Go', 'biblica-online-bible'),
            '/biblica/templates/rtb/widget/getButton' => __('Get', 'biblica-online-bible'),
            '/biblica/templates/rtb/widget/searchLabel' => __('Search', 'biblica-online-bible'),
            // translators: abbreviation of the translation that will be searched (e.g. NIV)
            '/biblica/templates/rtb/widget/searchPlaceHolder' => __('Search the %s Bible', 'biblica-online-bible'),
            '/widget/disabled/unverified-bible-api-key' => __('The Bible widget is disabled. In order to use the Bible widget you must enter a valid key in the Bible API Key field in settings.', 'biblica-online-bible'),
            '/widget/disabled/page-ids' => __('The Bible widget is disabled. In order to use the Bible widget you must set the Bible Reader Page field or the Bible Search Page field in settings.', 'biblica-online-bible'),
            '/widget/disabled/unavailable' => __('The Bible widget is not available right now. Please try again later.', 'biblica-online-bible'),
        ];
    }

    public function render(?string $template = null): string
    {
        $defaultTemplate = 'bible-widget.twig';
        $twigTemplate = null;
        try {
            $data = $this->getTemplateData();

            $twigContext = [
                'data' => $data,
                'strings' => $this->getStrings(),
            ];

            $twigTemplate = $template !== null ? $template : $defaultTemplate;
            $templateLocation = $this->getPlugin()->getPluginPath() . 'templates';
            $twig = TwigManager::createEnvironment($templateLocation);

            $html = $twig->render($twigTemplate, $twigContext);
        } catch (Exception $e) {
            $this->logException($e);
            if ($twigTemplate === $defaultTemplate) {
                $html = '<div class="disabled-message">' . $this->getString('/widget/disabled/unavailable') . '</div>';
            } else {
                $html = '';
            }
        }

        return $html;
    }

    /**
     * @return BibleWidgetDto
     * @throws OnlineBibleException
     * @throws Exception
     */
    public function getTemplateData(): BibleWidgetDto
    {
        $data = new BibleWidgetDto();

        $data->disabledMessage = $this->getDisabledMessage();
        if ($data->disabledMessage !== null) {
            return $data;
        }

        $data->showWidget = $this->getSelectedTranslation() !== null;
        $data->showReadForm = $this->showReadForm();
        $data->showSearchForm = $this->showSearchForm();
        $data->submitUrl = $this->getSubmitUrl();
        $data->bibleReaderUrl = $this->getBibleReaderUrl();
        $data->bibleReaderUrlType = $this->getBibleReaderUrlType();
        $data->bibleSearchUrl = $this->getBibleSearchUrl();

        $data->activeTranslations = $this->getActiveTranslations();
        $data->selectedTranslation = $this->getSelectedTranslation();
        $data->selectedBook = $this->getSelectedBook();
        $data->selectedChapter = $this->getSelectedChapter();
        $data->searchPlaceholder = $this->getSearchPlaceholder();

        return $data;
    }

    /**
     * @throws Exception
     */
    public function getDisabledMessage(): ?string
    {
        $options = $this->getPlugin()->getOptions('general');
        $bibleApiKey = $options->getOption('bibleApiKey');
        $verifiedBibleApiKey = $options->getOption('verifiedBibleApiKey');
        $bibleReaderPageId = $options->getOption('bibleReaderPageId');
        $bibleSearchPageId = $options->getOption('bibleSearchPageId');
        if ($bibleApiKey !== $verifiedBibleApiKey) {
            $message = $this->getString('/widget/disabled/unverified-bible-api-key');
        } elseif ($bibleReaderPageId === 0 && $bibleSearchPageId === 0) {
            $message = $this->getString('/widget/disabled/page-ids');
        } else {
            $message = null;
        }

        if (
            $this->getSelectedTranslation() === null ||
            $this->getSelectedBook() === null ||
            $this->getSelectedChapter() === null
        ) {
            $message = $this->getString('/widget/disabled/unavailable');
        }

        return $message;
    }

    private function showSearchForm(): bool
    {
        if (strtolower($this->shortCodeAttributes['search']) === 'false') {
            $showSearchForm = false;
        } else {
            $showSearchForm = (bool)$this->shortCodeAttributes['search'];
        }

        $options = $this->getPlugin()->getOptions('general');
        if ($options->getOption('bibleSearchPageId') === 0) {
            $showSearchForm = false;
        }

        return $showSearchForm;
    }

    private function showReadForm(): bool
    {
        if (strtolower($this->shortCodeAttributes['read']) === 'false') {
            $showReadForm = false;
        } else {
            $showReadForm = (bool)$this->shortCodeAttributes['read'];
        }

        $options = $this->getPlugin()->getOptions('general');
        if ($options->getOption('bibleReaderPageId') === 0) {
            $showReadForm = false;
        }

        return $showReadForm;
    }

    /**
     * @throws OnlineBibleException
     */
    private function getUrlBuilder(): BibleReaderUrlBuilderInterface
    {
        return $this->getPlugin()->getUrlBuilder();
    }

    /**
     * @return array
     */
    protected function getActiveTranslations(): array
    {
        if ($this->availableTranslations === null) {
            $this->availableTranslations = [];
            $translations = $this->getTranslationService()->getActiveTranslations();

            foreach ($translations as $translation) {
                $this->availableTranslations[] = $translation;
            }

            $collator = new Collator(get_locale());
            usort(
                $this->availableTranslations,
                function (Translation $translation1, Translation $translation2) use ($collator) {
                    return $collator->compare($translation1->getLanguage()->name, $translation2->getLanguage()->name);
                }
            );
        }

        return $this->availableTranslations;
    }

    /**
     * @throws Exception
     */
    protected function getSelectedTranslation(): ?Translation
    {
        if ($this->selectedTranslation === null) {
            $this->selectedTranslation = $this->getTranslationService()->getTranslation($this->getTranslationId());

            if ($this->selectedTranslation === null) {
                $options = $this->getPlugin()->getOptions('translations');
                $this->selectedTranslation = $this->getTranslationService()->getTranslation(
                    $options->getOption('defaultTranslationId')
                );
            }
        }

        return $this->selectedTranslation;
    }

    protected function getSelectedBook(): ?Book
    {
        $bookUrlSegment = $this->getBookUrlSegment();
        if ($this->selectedBook === null && $bookUrlSegment !== null && $bookUrlSegment !== '') {
            foreach ($this->getSelectedTranslation()->books as $book) {
                if ($book->urlSegment === $bookUrlSegment) {
                    $this->selectedBook = $book;
                }
            }
        }

        if ($this->selectedBook === null) {
            $this->selectedBook = $this->getSelectedTranslation()->books[0];
        }

        return $this->selectedBook;
    }

    protected function getSelectedChapter(): ?Chapter
    {
        $chapterName = $this->getChapterName();
        if ($this->selectedChapter === null && $chapterName !== null && $chapterName !== '') {
            foreach ($this->getSelectedBook()->chapters as $chapter) {
                if ($chapter->name === $chapterName) {
                    $this->selectedChapter = $chapter;
                }
            }
        }

        if ($this->selectedChapter === null) {
            $this->selectedChapter = $this->getSelectedBook()->chapters[0];
        }

        return $this->selectedChapter;
    }

    public function getBibleReaderUrl(): string
    {
        $options = $this->getPlugin()->getOptions('general');

        return get_permalink($options->getOption('bibleReaderPageId'));
    }

    /**
     * @throws OnlineBibleException
     */
    public function getBibleReaderUrlType(): string
    {
        return $this->getUrlBuilder()->getUrlType();
    }

    /**
     * @throws OnlineBibleException
     */
    public function getSubmitUrl(): string
    {
        $url = $this->getUrlBuilder()->getUrl(
            $this->getSelectedTranslation(),
            $this->getSelectedBook(),
            $this->getSelectedChapter()
        );

        return mb_convert_case($url, MB_CASE_LOWER);
    }

    public function getBibleSearchUrl(): string
    {
        $options = $this->getPlugin()->getOptions('general');

        return get_permalink($options->getOption('bibleSearchPageId'));
    }

    public function getSearchPlaceholder(): string
    {
        return sprintf(
            $this->strings['/biblica/templates/rtb/widget/searchPlaceHolder'],
            $this->getSelectedTranslation()->getAbbreviation()
        );
    }
}
