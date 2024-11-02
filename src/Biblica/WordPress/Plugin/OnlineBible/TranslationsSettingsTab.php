<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\Bible\Translations\Entities\TranslationInfo;
use Biblica\WordPress\Plugin\Common\DropDownItem;
use Biblica\WordPress\Plugin\Common\Options;
use Biblica\WordPress\Plugin\Common\SettingsTab;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;

class TranslationsSettingsTab extends SettingsTab
{
    public function __construct(WordPressPlugin $plugin, Options $options)
    {
        parent::__construct(
            $plugin,
            'translations',
            'Translations',
            $options,
            'translations-settings-tab.twig'
        );

        $this->strings = $this->strings + [
                '/admin/options/defaultTranslationIdLabel' => __('Default Translation', 'biblica-online-bible'),
                '/admin/options/defaultTranslationIdHelpText' => __('The default Bible translation shown by the Bible reader and widget. You can only select from the Bible translations which have been enabled.', 'biblica-online-bible'),
                '/admin/options/availableTranslationsHelpText' => __('These are the translations provided by your Api.Bible account. Enable the translations you would like to be shown in the Bible reader and widget.', 'biblica-online-bible'),
                '/admin/options/translations/enabledColumnHeader' => __('Enabled', 'biblica-online-bible'),
                '/admin/options/translations/abbreviationColumnHeader' => __('Abbreviation', 'biblica-online-bible'),
                '/admin/options/translations/customAbbreviationColumnHeader' => __('Custom Abbrev', 'biblica-online-bible'),
                '/admin/options/translations/idColumnHeader' => __('ID', 'biblica-online-bible'),
                '/admin/options/translations/languageColumnHeader' => __('Language', 'biblica-online-bible'),
                '/admin/options/translations/nameColumnHeader' => __('Name', 'biblica-online-bible'),
                '/admin/options/translations/customNameColumnHeader' => __('Custom Name', 'biblica-online-bible'),
                '/admin/options/submitButton' => __('Save Changes', 'biblica-online-bible'),
            ];
    }

    protected function getTemplateData(): TranslationsSettingsDto
    {
        $data = new TranslationsSettingsDto();

        $data->settingsFields = $this->getSettingsFields($this->getOptions()->getGroup());
        $data->translationDropDownItems = $this->getTranslationDropDownItems();
        $data->translationInformation = $this->getTranslationInformation();

        return $data;
    }

    public function getDefaultTranslationId(): ?string
    {
        $defaultTranslationId = $this->getOptions()->getOption('defaultTranslationId');

        return $defaultTranslationId ?? null;
    }

    /**
     * @return DropDownItem[]
     */
    public function getTranslationDropDownItems(): array
    {
        $items[] = new DropDownItem($this->getStrings()['/admin/options/page-dropdown/option-none'], '', false);
        $defaultTranslationId = $this->getDefaultTranslationId();
        $translations = $this->getTranslationService()->getActiveTranslations();
        foreach ($translations as $translation) {
            $selected = $translation->getId() === $defaultTranslationId;
            $text = $translation->getName() . ' (' . $translation->getId() . ')';
            $items[] = new DropDownItem($text, $translation->getId(), $selected);
        }

        return $items;
    }

    public function getTranslationInformation(): array
    {
        $dropdowns = [];
        $availableTranslations = $this->getTranslationService()->getAvailableTranslations();
        uasort($availableTranslations, function (TranslationInfo $item1, TranslationInfo $item2) {
            $compareLanguage = strcmp($item1->getLanguage()->name, $item2->getLanguage()->name);
            if ($compareLanguage !== 0) {
                return $compareLanguage;
            }
            return strcmp($item1->getName(), $item2->getName());
        });

        $count = 1;
        foreach ($availableTranslations as $translationInfo) {
            $translationId = $translationInfo->getId();
            $enabled = $this->getTranslationService()->isEnabled($translationId);
            $dropdowns[$translationId]['count'] = $count;
            $dropdowns[$translationId]['enabled'] = $enabled;
            $dropdowns[$translationId]['id'] = $translationId;
            $dropdowns[$translationId]['abbreviation'] = $translationInfo->getAbbreviation('eng');
            $dropdowns[$translationId]['customAbbreviation'] = $translationInfo->getAbbreviation('custom');
            $dropdowns[$translationId]['language'] = $translationInfo->getLanguage()->name;
            $dropdowns[$translationId]['name'] = $translationInfo->getName('eng');
            $dropdowns[$translationId]['customName'] = $translationInfo->getName('custom');
            $count++;
        }
        return $dropdowns;
    }
}
