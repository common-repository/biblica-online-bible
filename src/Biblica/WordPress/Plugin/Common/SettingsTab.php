<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

use Biblica\Util\TwigManager;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

abstract class SettingsTab extends BibleApiPage
{
    protected string $id;
    protected string $title;
    protected Options $options;
    private string $twigTemplate;
    /** @var Message[] */
    private array $messages;

    abstract protected function getTemplateData(): object;

    public function __construct(WordPressPlugin $plugin, string $id, string $title, Options $options, string $twigTemplate)
    {
        parent::__construct($plugin);

        $this->id = $id;
        $this->title = $title;
        $this->options = $options;
        $this->twigTemplate = $twigTemplate;
        $this->messages = [];

        $this->strings = $this->strings + [
            '/admin/options/page-dropdown/option-none' => __('-- Select --', 'biblica-online-bible'),
            '/admin/options/submitButton' => __('Save Changes', 'biblica-online-bible'),
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    protected function getSettingsFields(string $optionGroup): string
    {
        ob_start();
        settings_fields($optionGroup);

        return ob_get_clean();
    }

    /**
     * @param int $selected
     * @param string $name
     * @return string
     */
    public function getPageDropDown(int $selected, string $name): string
    {
        ob_start();
        wp_dropdown_pages([
            'selected' => $selected,
            'name' => $name,
            'show_option_none' => $this->strings['/admin/options/page-dropdown/option-none'],
            'option_none_value' => '0'
        ]);

        return ob_get_clean();
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
        $templateLocation = $this->getPlugin()->getPluginPath() . 'templates';
        $twig = TwigManager::createEnvironment($templateLocation);

        return $twig->render($this->twigTemplate, $twigContext);
    }
}
