<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

class HeaderTagService implements HeaderTagServiceInterface
{
    protected array $tags = [];
    protected ?SeoPageInterface $seoPage = null;
    /** @var array */
    protected array $allowedElements = [
        'base' => [],
        'link' => [
            'as' => [],
            'disabled' => [],
            'fetchpriority' => [],
            'href' => [],
            'integrity' => [],
            'media' => [],
            'prefetch' => [],
            'referrerpolicy' => [],
            'rel' => [],
            'title' => [],
            'type' => [],
        ],
        'meta' => [
            'name' => [],
            'http-equiv' => [],
            'content' => [],
        ],
        'noscript' => [],
        'title' => [],
    ];

    public function setPage(SeoPageInterface $seoPage): void
    {
        $this->seoPage = $seoPage;
    }

    public function addTag(string $tag): void
    {
        $this->tags[] = $tag;
    }

    public function insertTags(): void
    {
        add_action('wp_head', function () {
            echo $this->renderTags();
        });
    }

    public function renderTags(): string
    {
        $output = '';
        foreach ($this->tags as $tag) {
            $output .= $tag . "\n";
        }

        if ($output !== '') {
            $output = "\n" . $output;
            $output = wp_kses($output, $this->allowedElements);
        }

        return $output;
    }
}
