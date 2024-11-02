<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\ApiDotBibleApi\Api;

use Biblica\Bible\ApiDotBibleApi\Utils\API;
use Biblica\Util\LogManager;
use Biblica\WordPress\Plugin\Common\Settings;

/**
 * Class AudioBibleChapterEndpoint
 * @package Biblica\Bible\ApiDotBibleApi\Api
 */
class AudioBibleChapterEndpoint
{
    private string $bibleId;
    private string $chapter;

    public function __construct()
    {
        $this->bibleId = sanitize_key($_REQUEST['bible-id']);
        $this->chapter = sanitize_text_field($_REQUEST['chapter']);
    }

    public function getUrl(): string
    {
        $urlFormat = 'https://api.scripture.api.bible/v1/audio-bibles/%1$s/chapters/%2$s';
        $url = sprintf($urlFormat, $this->bibleId, $this->chapter);

        return $url;
    }

    public function render(): string
    {
        $response = API::call($this->getUrl(), ['fums-version' => '3'], true);

        return $response === null ? '{}' : $response;
    }
}
