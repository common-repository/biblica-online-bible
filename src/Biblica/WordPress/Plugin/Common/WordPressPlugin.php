<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

use Biblica\WordPress\Plugin\OnlineBible\BibleReaderUrlBuilderInterface;
use Biblica\WordPress\Plugin\OnlineBible\Exception\OnlineBibleException;

class WordPressPlugin
{
    private string $pluginDir;
    private string $pluginName;
    private string $pluginPath;
    private string $pluginUrl;
    /** @var Options[] */
    private array $options = [];
    private ?BibleReaderUrlBuilderInterface $urlBuilder = null;

    public function __construct(string $pluginPath)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginDir = substr($pluginPath, strlen(WP_PLUGIN_DIR) + 1);
        $this->pluginName = rtrim($this->pluginDir, '/');
        $this->pluginUrl = plugins_url($this->pluginName);
    }

    public function getPluginDir(): string
    {
        return $this->pluginDir;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function getPluginPath(): string
    {
        return $this->pluginPath;
    }

    public function getPluginUrl(): string
    {
        return $this->pluginUrl;
    }

    public function isPluginActive(string $plugin): bool
    {
        if (!function_exists( 'is_plugin_active')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        return is_plugin_active($plugin);
    }

    public function getOptions(?string $optionsIndex = null): ?Options
    {
        if ($optionsIndex === null) {
            $options = reset($this->options);
            $options = $options === false ? null : $options;
        } else {
            $options = $this->options[$optionsIndex] ?? null;
        }

        return $options;
    }

    public function setOptions(string $optionsIndex, Options $options): void
    {
        $this->options[$optionsIndex] = $options;
    }

    public function setUrlBuilder(BibleReaderUrlBuilderInterface $urlBuilder): void
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @throws OnlineBibleException
     */
    public function getUrlBuilder(): BibleReaderUrlBuilderInterface
    {
        if ($this->urlBuilder === null) {
            throw new OnlineBibleException('Url builder is not set in ' . __CLASS__);
        }

        return $this->urlBuilder;
    }


    public function enqueueScripts(array $scripts): void
    {
        foreach ($scripts as $scriptHandle => $scriptParameters) {
            if ($scriptParameters['register'] === true) {
                wp_register_script(
                    $scriptHandle,
                    $scriptParameters['source'] ?? '',
                    $scriptParameters['depend'] ?? [],
                    $scriptParameters['version'] ?? null,
                    $scriptParameters['in-footer'] ?? true
                );
            }
            wp_enqueue_script($scriptHandle);
        }
    }

    public function enqueueStyles(array $styles): void
    {
        foreach ($styles as $styleHandle => $styleParameters) {
            if ($styleParameters['register'] === true) {
                wp_register_style(
                    $styleHandle,
                    $styleParameters['source'] ?? '',
                    $styleParameters['depend'] ?? [],
                    $styleParameters['version'] ?? null,
                    $styleParameters['media'] ?? 'all'
                );
            }
            wp_enqueue_style($styleHandle);
        }
    }

    public function hasQueryString(): bool
    {
        $queryString = $_SERVER['QUERY_STRING'];

        return isset($queryString) && $queryString !== '';
    }
}
