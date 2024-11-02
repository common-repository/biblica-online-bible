<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

class WordPressPage
{
    private WordPressPlugin $plugin;

    public function __construct(WordPressPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getPlugin(): WordPressPlugin
    {
        return $this->plugin;
    }

    protected function getVar(string $var, int $maxLength = 0): ?string
    {
        global $wp_query;
        $value = null;

        if ($wp_query !== null) {
            $value = $wp_query->query_vars[$var] ?? null;
        }
        if ($value === null) {
            $value = $_REQUEST[$var] ?? null;
        }
        if ($value !== null) {
            $value = rawurldecode($value);
        }
        if ($value !== null && $maxLength > 0) {
            $value = substr($value, 0, $maxLength);
        }
        if ($value !== null) {
            $value = sanitize_text_field($value);
            $value = trim($value);
        }

        return $value;
    }
}
