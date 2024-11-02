<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Exception;
use Biblica\Bible\ApiDotBibleApi\Components\TranslationService;
use Biblica\Util\CacheManager;
use Biblica\Util\TwigManager;
use Biblica\WordPress\Plugin\Common\BibleApiPage;
use Biblica\WordPress\Plugin\Common\Message;
use Biblica\WordPress\Plugin\Common\Options;
use Biblica\WordPress\Plugin\Common\SettingsTab;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class OnlineBibleSettingsPage extends BibleApiPage
{
    private array $tabs = [];
    /** @var Message[] */
    private array $messages = [];

    /**
     * @param WordPressPlugin $plugin
     * @param Options $generalOptions
     * @param Options $translationsOptions
     * @param bool $render
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function __construct(WordPressPlugin $plugin, Options $generalOptions, Options $translationsOptions, bool $render = false)
    {
        parent::__construct($plugin);

        $this->strings = $this->strings + [
            '/admin/options/pageHeading' => __('Biblica Online Bible Settings', 'biblica-online-bible'),
            '/admin/options/clear-bible-cache-button/text' => __('Clear Cache', 'biblica-online-bible'),
            '/admin/settings/set-bible-api-key' => __('You must set the Bible API key in order to use this plugin. You may obtain an API key from https://scripture.api.bible/.', 'biblica-online-bible'),
            '/admin/settings/invalid-bible-api-key' => __('Your Bible API Key is invalid. Please make sure that you have entered it correctly. You may obtain an API key from https://scripture.api.bible/.', 'biblica-online-bible'),
        ];

        $generalSettingsTab = new GeneralSettingsTab($this->getPlugin(), $generalOptions);
        $this->tabs[$generalSettingsTab->getId()] = $generalSettingsTab;
        if ($this->bibleApiKeyIsVerified($generalOptions, $translationsOptions)) {
            $translationsSettingsTab = new TranslationsSettingsTab($this->getPlugin(), $translationsOptions);
            $this->tabs[$translationsSettingsTab->getId()] = $translationsSettingsTab;
        }
        $settingsTabs = apply_filters('biblica_ob_settings_tabs', []);
        foreach ($settingsTabs as $newTab) {
            if ($newTab instanceof SettingsTab) {
                $this->tabs[$newTab->getId()] = $newTab;
            }
        }

        if ($render) {
            echo $this->render();
        }
    }

    /**
     * @return string
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function render(): string
    {
        $twigContext = [
            'data' => $this->getTemplateData(),
            'strings' => $this->getStrings(),
        ];
        $twigTemplate = 'online-bible-settings-page.twig';

        $templateLocation = $this->getPlugin()->getPluginPath() . 'templates';
        $twig = TwigManager::createEnvironment($templateLocation);

        return $twig->render($twigTemplate, $twigContext);
    }

    public function getTemplateData(): OnlineBibleSettingsDto
    {
        $data = new OnlineBibleSettingsDto();

        $data->options = $this->getOptionsByName(Settings::$onlineBibleOptionName, Settings::$onlineBibleOptionValues);
        $this->checkApiKey();
        $data->currentTabId = $this->getCurrentTabId();

        $data->tabs = $this->tabs;
        $data->form = $this->tabs[$this->getCurrentTabId()]->render();
        $data->messages = $this->getMessages();

        return $data;
    }

    /**
     * @throws Exception
     */
    private function bibleApiKeyIsVerified(Options $generalOptions, Options $translationsOptions): bool
    {
        $bibleApiKey = $generalOptions->getOption('bibleApiKey');
        $verifiedBibleApiKey = $generalOptions->getOption('verifiedBibleApiKey');
        if ($bibleApiKey !== '' && $bibleApiKey !== $verifiedBibleApiKey) {
            if ($this->verifyBibleApiKey($translationsOptions) === true) {
                $verifiedBibleApiKey = $bibleApiKey;
            } else {
                $verifiedBibleApiKey = null;
            }
        } elseif ($bibleApiKey === '' && $verifiedBibleApiKey === '') {
            $verifiedBibleApiKey = null;
        }
        $generalOptions->setOption('verifiedBibleApiKey', $verifiedBibleApiKey);
        $generalOptions->save();
        if ($bibleApiKey === $verifiedBibleApiKey) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    private function verifyBibleApiKey(Options $translationsOptions): bool
    {
        $defaultTranslationIds = [
            '71c6eab17ae5b667-01', // New International Version (NIV)
            '5b888a42e2d9a89d-01', // New International Reader's Version (NIRV)
            'de4e12af7f28f599-02', // King James Version (KJV)
        ];

        CacheManager::getObjectCache()->invalidateCache();
        $translationService = new TranslationService();
        $availableTranslations = $translationService->getAvailableTranslations();
        if (count($availableTranslations) > 0) {
            $newDefaultTranslationId = $availableTranslations[array_key_first($availableTranslations)]->getId();
            foreach ($defaultTranslationIds as $translationId) {
                if (isset($availableTranslations[$translationId])) {
                    $newDefaultTranslationId = $translationId;
                    break;
                }
            }

            // TODO: Use existing translations settings when switching to a new api key.
            $newTranslationsEnabled = [];
            foreach ($availableTranslations as $translationInfo) {
                $enabled = $translationInfo->getId() === $newDefaultTranslationId;
                $newTranslationsEnabled[$translationInfo->getId()]['enabled'] = $enabled;
                $newTranslationsEnabled[$translationInfo->getId()]['customName'] = null;
                $newTranslationsEnabled[$translationInfo->getId()]['customAbbreviation'] = null;
            }

            $translationsOptions->setOption('translations', $newTranslationsEnabled);
            $translationsOptions->setOption('defaultTranslationId', $newDefaultTranslationId);
            $translationsOptions->save();

            return true;
        } else {
            return false;
        }
    }

    public function getCurrentTabId(): string
    {
        $currentTab = $this->getVar('tab');
        if (!isset($currentTab)) {
            $currentTab = 'general';
        }

        return $currentTab;
    }

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return Message[]
     */
    private function getMessages(): array
    {
        $activeTab = $this->getCurrentTabId();
        if (isset($this->tabs[$activeTab])) {
            $this->messages = $this->messages + $this->tabs[$activeTab]->getMessages();
        }

        return $this->messages;
    }

    private function checkApiKey(): void
    {
        $options = $this->getPlugin()->getOptions('general');
        $bibleApiKey = $options->getOption('bibleApiKey');
        $verifiedBibleApiKey = $options->getOption('verifiedBibleApiKey');

        if (!isset($bibleApiKey) || $bibleApiKey === '') {
            $this->addMessage(new Message(
                $this->strings['/admin/settings/set-bible-api-key'],
                Message::TYPE_ERROR,
                false
            ));
        } elseif ($bibleApiKey !== $verifiedBibleApiKey) {
            $this->addMessage(new Message(
                $this->strings['/admin/settings/invalid-bible-api-key'],
                Message::TYPE_ERROR,
                false
            ));
        }
    }
}
