<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Exception;
use Biblica\Bible\ApiDotBibleApi\Api\AudioBibleChapterEndpoint;
use Biblica\Bible\ApiDotBibleApi\Components\TranslationService;
use Biblica\Util\CacheManager;
use Biblica\Util\ContainerManager;
use Biblica\Util\FileSystemCache;
use Biblica\Util\TwigManager;
use Biblica\WordPress\Plugin\Common\HeaderTagService;
use Biblica\WordPress\Plugin\Common\HeaderTagServiceInterface;
use Biblica\WordPress\Plugin\Common\Options;
use Biblica\WordPress\Plugin\Common\WordPressPlugin;
use Pimple\Container;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class OnlineBiblePlugin extends WordPressPlugin
{
    private Container $container;
    private ?HeaderTagServiceInterface $headerTagService = null;
    private ?BibleReader $bibleReader = null;
    private ?BibleSearch $bibleSearch = null;
    private ?BibleWidget $bibleWidget = null;

    public function __construct(string $pluginPath)
    {
        parent::__construct($pluginPath);

        ContainerManager::setContainer(new Container());
    }

    public function initialize(): void
    {
        $sanitizeGeneral = function (array $options): array {
            $sanitizedOptions['bibleApiKey'] = sanitize_key($options['bibleApiKey']);
            $verifiedKey = $options['verifiedBibleApiKey'] ?? null;
            $sanitizedOptions['verifiedBibleApiKey'] = is_string($verifiedKey) ? sanitize_key($verifiedKey) : null;
            $sanitizedOptions['enablePolyglot'] = $this->sanitizeCheckBox($options['enablePolyglot'] ?? false);
            $sanitizedOptions['showReaderSearchForm'] = $this->sanitizeCheckBox($options['showReaderSearchForm'] ?? false);
            $sanitizedOptions['bibleReaderPageId'] = $this->sanitizeInteger(
                $options['bibleReaderPageId'] ?? null,
                Settings::$onlineBibleOptionValues['bibleReaderPageId']
            );
            $sanitizedOptions['bibleSearchPageId'] = $this->sanitizeInteger(
                $options['bibleSearchPageId'] ?? null,
                Settings::$onlineBibleOptionValues['bibleSearchPageId']
            );

            return $sanitizedOptions;
        };

        $generalOptions = new Options(
            Settings::$onlineBibleOptionName,
            Settings::$onlineBibleOptionGroup,
            Settings::$onlineBibleOptionValues,
            $sanitizeGeneral
        );
        $this->setOptions('general', $generalOptions);

        $sanitizeTranslations = function (array $options): array {
            $sanitizedOptions = [];
            $sanitizedOptions['defaultTranslationId'] = sanitize_text_field($options['defaultTranslationId']);
            foreach ($options['translations'] as $translationId => $translationOptions) {
                $enabled = $this->sanitizeCheckBox($translationOptions['enabled'] ?? false);
                $sanitizedOptions['translations'][$translationId]['enabled'] = $enabled;
                $customAbbreviation = sanitize_text_field($translationOptions['customAbbreviation']);
                $customAbbreviation = $customAbbreviation === '' ? null : $customAbbreviation;
                $sanitizedOptions['translations'][$translationId]['customAbbreviation'] = $customAbbreviation;
                $customName = sanitize_text_field($translationOptions['customName']);
                $customName = $customName === '' ? null : $customName;
                $sanitizedOptions['translations'][$translationId]['customName'] = $customName;
            }

            return $sanitizedOptions;
        };

        $translationsOptions = new Options(
            Settings::$translationOptionName,
            Settings::$translationOptionGroup,
            Settings::$translationOptionValues,
            $sanitizeTranslations
        );
        $this->setOptions('translations', $translationsOptions);

        register_activation_hook(
            $this->getPluginDir() . $this->getPluginName() . '.php',
            ['\Biblica\WordPress\Plugin\OnlineBible\OnlineBibleSetup', 'onActivate']
        );
        register_uninstall_hook(
            $this->getPluginDir() . $this->getPluginName() . '.php',
            ['\Biblica\WordPress\Plugin\OnlineBible\OnlineBibleSetup', 'onUninstall']
        );
        register_deactivation_hook(
            $this->getPluginDir() . $this->getPluginName() . '.php',
            ['\Biblica\WordPress\Plugin\OnlineBible\OnlineBibleSetup', 'onDeactivate']
        );

        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
        add_action('wp', [$this, 'onWp']);
        add_action('wp_head', [$this, 'onWpHead']);
        add_action('admin_init', [$this, 'onAdminInit']);
        add_action('admin_menu', [$this, 'onAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'onAdminEnqueueScripts']);
        add_filter('load_textdomain_mofile', [$this, 'onLoadTextDomainMofile'], 10, 2);
        add_filter('query_vars', [$this, 'onQueryVars']);
        add_action('wp_enqueue_scripts', [$this, 'onWpEnqueueScripts']);
        add_filter('style_loader_tag', [$this, 'onStyleLoaderTag'], 10, 2);

        add_shortcode('biblica-bible-reader', [$this, 'shortcodeBiblicaBibleReader']);
        add_shortcode('biblica-bible-search', [$this, 'shortcodeBiblicaBibleSearch']);
        add_shortcode('biblica-bible-widget', [$this, 'shortcodeBiblicaBibleWidget']);

        add_action('wp_ajax_nopriv_bible_widget_refresh', [$this, 'onWpAjaxBibleWidgetRefresh']);
        add_action('wp_ajax_bible_widget_refresh', [$this, 'onWpAjaxBibleWidgetRefresh']);
        add_action('wp_ajax_nopriv_audio_bible_chapter', [$this, 'onWpAjaxAudioBibleChapter']);
        add_action('wp_ajax_audio_bible_chapter', [$this, 'onWpAjaxAudioBibleChapter']);
        add_action('wp_ajax_clear_bible_cache_button', [$this, 'onWpAjaxClearBibleCacheButton']);

        $this->loadOptions();
    }

    /**
     * @throws Exception
     */
    public function onPluginsLoaded(): void
    {
        $this->container = ContainerManager::getContainer();

        $containerSettings = [
            'cache' => [
                'isFactory' => false,
                'value' => fn() => new FileSystemCache(get_site_url(), CacheManager::getObjectCacheDirectory(), 604800),
            ],
            'urlBuilder' => [
                'isFactory' => false,
                'value' => fn() => new QueryStringBibleReaderUrlBuilder(),
            ],
            'headerTagService' => [
                'isFactory' => true,
                'value' => fn() => new HeaderTagService(),
            ],
        ];
        $containerSettings = apply_filters('biblica_ob_container_settings', $containerSettings);
        foreach ($containerSettings as $settingKey => $settingValue) {
            if ($settingValue['isFactory']) {
                $this->container[$settingKey] = $this->container->factory($settingValue['value']);
            } else {
                $this->container[$settingKey] = $settingValue['value'];
            }
        }

        CacheManager::setObjectCache($this->container['cache']);
        $this->setUrlBuilder($this->container['urlBuilder']);
        $this->setHeaderTagService($this->container['headerTagService']);

        load_plugin_textdomain('biblica-online-bible', false, $this->getPluginDir() . '/languages');
    }

    public function onWp(): void
    {
        if (get_queried_object_id() === Settings::$bibleReaderPageId) {
            $this->getHeaderTagService()->setPage($this->getBibleReader());
        }
    }

    public function getHeaderTagService(): HeaderTagServiceInterface
    {
        // TODO: throw exception for missing header tag service
        return $this->headerTagService;
    }

    public function setHeaderTagService(HeaderTagServiceInterface $headerTagService): void
    {
        $this->headerTagService = $headerTagService;
    }

    public function onWpHead(): void
    {
        $headerTags = [];
        $headerTags = apply_filters('biblica_ob_header_tags', $headerTags);
        foreach($headerTags as $headerTag) {
            $this->getHeaderTagService()->addTag($headerTag);
        }
        echo $this->getHeaderTagService()->renderTags();
    }

    public function onAdminInit(): void
    {
        $this->getOptions('general')->register();
        $this->getOptions('translations')->register();
    }

    public function onAdminMenu(): void
    {
        add_options_page(
            'Biblica Bible Reader Settings',
            'Bible Reader',
            'manage_options',
            'biblica-ob-options',
            [$this, 'renderOptionsPage']
        );

        add_action('update_option_' . Settings::$onlineBibleOptionName, [$this, 'onUpdateOnlineBibleOption'], 10, 2);
        add_action('update_option_' . Settings::$translationOptionName, [$this, 'onUpdateTranslationOption'], 10, 2);
    }

    /**
     * @throws Exception
     */
    public function onUpdateOnlineBibleOption($oldValue, $newValue): void
    {
        if ($newValue['bibleApiKey'] !== $oldValue['bibleApiKey']) {
            CacheManager::getObjectCache()->invalidateCache();
        }
    }

    /**
     * @throws Exception
     */
    public function onUpdateTranslationOption(): void
    {
        CacheManager::getObjectCache()->invalidateTag(TranslationService::CACHE_TAG);
    }

    public function onAdminEnqueueScripts(): void
    {
        $pluginUrl = $this->getPluginUrl();

        $scripts = [
            'jquery' => [
                'register' => false
            ],
            'biblica-ob-jbox' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/jbox/jBox.all.min.js',
                'depend' => ['jquery'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-admin' => [
                'register' => true,
                'source' => $pluginUrl . '/js/admin.min.js',
                'depend' => ['jquery', 'biblica-ob-jbox'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
        ];
        $scripts = apply_filters('biblica_ob_admin_scripts', $scripts);
        $this->enqueueScripts($scripts);

        $styles = [
            'biblica-ob-jbox' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/jbox/jBox.all.min.css',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-admin' => [
                'register' => true,
                'source' => $pluginUrl . '/css/admin.min.css',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
        ];
        $styles = apply_filters('biblica_ob_admin_styles', $styles);
        $this->enqueueStyles($styles);
    }

    private function loadOptions(): void
    {
        Settings::$bibleApiKey = $this->getOptions('general')->getOption('bibleApiKey');
        Settings::$verifiedBibleApiKey = $this->getOptions('general')->getOption('verifiedBibleApiKey');
        Settings::$enablePolyglot = $this->getOptions('general')->getOption('enablePolyglot');
        Settings::$showReaderSearchForm = $this->getOptions('general')->getOption('showReaderSearchForm');
        Settings::$bibleReaderPageId = (int)$this->getOptions('general')->getOption('bibleReaderPageId');
        Settings::$bibleSearchPageId = (int)$this->getOptions('general')->getOption('bibleSearchPageId');

        Settings::$defaultTranslationId = $this->getOptions('translations')->getOption('defaultTranslationId');
        Settings::$translations = $this->getOptions('translations')->getOption('translations');
    }

    /**
     * @throws SyntaxError|RuntimeError|LoaderError
     */
    public function renderOptionsPage(): void
    {
        new OnlineBibleSettingsPage(
            $this,
            $this->getOptions('general'),
            $this->getOptions('translations'),
            true
        );
    }

    public function onLoadTextDomainMofile($mofile, $domain): string
    {
        if ('biblica-online-bible' === $domain && false !== strpos($mofile, WP_LANG_DIR . '/plugins/')) {
            $locale = apply_filters('plugin_locale', determine_locale(), $domain);
            $mofile = $this->getPluginPath() . 'languages/' . $domain . '-' . $locale . '.mo';
        }
        return $mofile;
    }

    // Query vars for the online Bible pages
    public function onQueryVars($vars): array
    {
        // vars for online Bible page
        $vars[] = 'translation';
        $vars[] = 'book';
        $vars[] = 'chapter';
        $vars[] = 'compare';

        // vars for Bible search page
        $vars[] = 'q';
        $vars[] = 'q-ex';
        $vars[] = 'spage';

        return $vars;
    }

    /**
     * @throws Exception
     */
    public function shortcodeBiblicaBibleReader(): string
    {
        return $this->getBibleReader()->render();
    }

    public function shortcodeBiblicaBibleSearch(): string
    {
        return $this->getBibleSearch()->render();
    }

    public function shortcodeBiblicaBibleWidget($shortCodeAttributes = []): string
    {
        return $this->getBibleWidget($shortCodeAttributes)->render();
    }

    public function onWpAjaxBibleWidgetRefresh(): void
    {
        echo $this->getBibleWidget()->render('bible-widget-read-form.twig');
        wp_die();
    }

    private function apiAudioBibleChapter(): string
    {
        $endpoint = new AudioBibleChapterEndpoint();

        return $endpoint->render();
    }

    public function onWpAjaxAudioBibleChapter(): void
    {
        header("Content-Type: application/json");
        echo $this->apiAudioBibleChapter();
        wp_die();
    }

    /**
     * @throws Exception
     */
    public function onWpAjaxClearBibleCacheButton(): void
    {
        if (
            CacheManager::getObjectCache()->invalidateCache() &&
            CacheManager::clearObjectCacheDirectory() &&
            TwigManager::clearCache()
        ) {
            echo sprintf('{ "result": "Cache cleared on %s." }', date('Y-m-d H:i:s e'));
        } else {
            echo '{ "result": "Error clearing cache." }';
        }
        wp_die();
    }

    public function onWpEnqueueScripts(): void
    {
        $pluginUrl = $this->getPluginUrl();

        // List of all styles used by the Online Bible plugin and the information
        // needed to register them with WordPress
        $styles = [
            'wp-mediaelement' => [
                'register' => false,
            ],
            'biblica-ob-jquery-ui' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/jquery-ui-custom/jquery-ui.min.css',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-jbox' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/jbox/jBox.all.min.css',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-forms-block' => [
                'register' => true,
                'source' => $pluginUrl . '/css/forms-block.min.css',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-main' => [
                'register' => true,
                'source' => $pluginUrl . '/css/main.min.css',
                'depend' => [
                    'biblica-ob-forms-block',
                    'biblica-ob-jquery-ui'
                ],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-bible-reader' => [
                'register' => true,
                'source' => $pluginUrl . '/css/bible-reader.min.css',
                'depend' => ['biblica-ob-main'],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-bible-search' => [
                'register' => true,
                'source' => $pluginUrl . '/css/bible-search.min.css',
                'depend' => ['biblica-ob-main'],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-bible-widget' => [
                'register' => true,
                'source' => $pluginUrl . '/css/bible-widget.min.css',
                'depend' => ['biblica-ob-main'],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-scripture-styles' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/api-bible/scripture-styles.css',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'media' => 'all'
            ],
            'biblica-ob-googleapis-preconnect' => [
                'register' => true,
                'source' => 'https://fonts.googleapis.com',
                'depend' => [],
                'version' => null,
                'media' => 'all'
            ],
            'biblica-ob-gstatic-preconnect' => [
                'register' => true,
                'source' => 'https://fonts.gstatic.com',
                'depend' => [],
                'version' => null,
                'media' => 'all'
            ],
            'biblica-ob-google-fonts' => [
                'register' => true,
                'source' => 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Marcellus&display=swap',
                'depend' => [
                    'biblica-ob-googleapis-preconnect',
                    'biblica-ob-gstatic-preconnect'
                ],
                'version' => null,
                'media' => 'all'
            ],
        ];

        // List of all Javascript files used by the Biblica Online Bible plugin and the information
        // needed to register them with WordPress
        $scripts = [
            'jquery' => [
                'register' => false
            ],
            'jquery-ui-datepicker' => [
                'register' => false
            ],
            'mediaelement' => [
                'register' => false
            ],
            'biblica-ob-online-bible' => [
                'register' => true,
                'source' => $pluginUrl . '/js/biblica.onlinebible.min.js',
                'depend' => ['jquery'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-startup' => [
                'register' => true,
                'source' => $pluginUrl . '/js/startup.min.js',
                'depend' => [
                    'jquery',
                    'biblica-ob-online-bible',
                    'jquery-ui-datepicker'
                ],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-fums-lib' => [
                'register' => true,
                'source' => 'https://pkg.api.bible/fumsV3.min.js',
                'depend' => [],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-fums' => [
                'register' => true,
                'source' => $pluginUrl . '/js/fums.min.js',
                'depend' => ['biblica-ob-fums-lib'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-jbox' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/jbox/jBox.all.min.js',
                'depend' => ['jquery'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-selectric' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/selectric/selectric.min.js',
                'depend' => ['jquery'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
            'biblica-ob-scrollbar' => [
                'register' => true,
                'source' => $pluginUrl . '/lib/scrollbar/scrollbar.min.js',
                'depend' => ['jquery'],
                'version' => BIBLICA_OB_VERSION,
                'in-footer' => true
            ],
        ];

        // Register all CSS files with WordPress
        $styles = apply_filters('biblica_ob_styles', $styles);
        $this->enqueueStyles($styles);

        // Register all Javascript files with WordPress
        $scripts = apply_filters('biblica_ob_scripts', $scripts);
        $this->enqueueScripts($scripts);
    }

    public function onStyleLoaderTag($tag, $handle): string
    {
        if ($handle == 'biblica-ob-googleapis-preconnect' || $handle == 'biblica-ob-gstatic-preconnect') {
            $tag = str_replace("rel='stylesheet'", 'rel="preconnect"', $tag);
            $tag = str_replace('rel="stylesheet"', 'rel="preconnect"', $tag);

            $tag = str_replace("type='text/css'", '', $tag);
            $tag = str_replace('type="text/css"', '', $tag);

            $tag = str_replace("media='all'", '', $tag);
            $tag = str_replace('media="all"', '', $tag);
        }

        if ($handle == 'biblica-ob-gstatic-preconnect') {
            $tag = str_replace('rel="preconnect"', 'rel="preconnect" crossorigin', $tag);
        }

        return $tag;
    }

    private function getBibleReader(): BibleReader
    {
        if ($this->bibleReader === null) {
            $this->bibleReader = new BibleReader($this);
        }

        return $this->bibleReader;
    }

    private function getBibleSearch(): BibleSearch
    {
        if ($this->bibleSearch === null) {
            $this->bibleSearch = new BibleSearch($this);
        }

        return $this->bibleSearch;
    }

    private function getBibleWidget($shortCodeAttributes = []): BibleWidget
    {
        if ($this->bibleWidget === null) {
            $shortCodeAttributes = is_array($shortCodeAttributes) ? $shortCodeAttributes : [];
            $this->bibleWidget = new BibleWidget($this, $shortCodeAttributes);
        }

        return $this->bibleWidget;
    }
}
