<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\WordPress\Plugin\Common\Options;
use Biblica\WordPress\Plugin\Common\SettingsTab;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;

class GeneralSettingsTab extends SettingsTab
{
    public function __construct(WordPressPlugin $plugin, Options $options)
    {
        parent::__construct(
            $plugin,
            'general',
            'General',
            $options,
            'general-settings-tab.twig'
        );

        $this->strings = $this->strings + [
                '/admin/options/submitButton' => __('Save Changes', 'biblica-online-bible'),
                '/admin/options/bibleReaderPageIdLabel' => __('Bible Reader Page', 'biblica-online-bible'),
                '/admin/options/bibleReaderPageIdHelpText' => __('Select the WordPress page where the Bible reader shortcode has been installed. Bible search results will link to this page.', 'biblica-online-bible'),
                '/admin/options/bibleSearchPageIdLabel' => __('Bible Search Page', 'biblica-online-bible'),
                '/admin/options/bibleSearchPageIdHelpText' => __('Select the WordPress page where the Bible search shortcode has been installed. Search boxes on the Bible reader and widget will link to this page.', 'biblica-online-bible'),
                '/admin/options/enablePolyglotLabel' => __('Side-by-Side Translations', 'biblica-online-bible'),
                '/admin/options/enablePolyglotHelpText' => __('Enable the display of two translations side-by-side.', 'biblica-online-bible'),
                '/admin/options/showReaderSearchFormLabel' => __('Show Search Form', 'biblica-online-bible'),
                '/admin/options/showReaderSearchFormHelpText' => __('Show a search box on the Bible reader.', 'biblica-online-bible'),
                '/admin/options/bibleApiKeyLabel' => __('Bible API Key', 'biblica-online-bible'),
                '/admin/options/bibleApiKeyHelpText' => __('Enter the api key for your Api.Bible account. This plugin uses Bible text provided by Api.Bible and requires an Api.Bible account in order to function.', 'biblica-online-bible'),
                '/admin/options/page-dropdown/option-none' => __('-- Select --', 'biblica-online-bible'),
            ];
    }

    protected function getTemplateData(): GeneralSettingsDto
    {
        $data = new GeneralSettingsDto();

        $data->options = $this->getOptions()->toArray();
        $data->settingsFields = $this->getSettingsFields($this->getOptions()->getGroup());
        $data->bibleReaderPageDropDown = $this->getPageDropDown(
            $this->getOptions()->getOption('bibleReaderPageId'),
            $this->getOptions()->getName() . '[bibleReaderPageId]'
        );
        $data->bibleSearchPageDropDown = $this->getPageDropDown(
            $this->getOptions()->getOption('bibleSearchPageId'),
            $this->getOptions()->getName() . '[bibleSearchPageId]'
        );

        return $data;
    }
}
