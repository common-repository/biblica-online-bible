<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

class HttpParameters
{
    public static string $filter = "filter";
    public static string $page = "spage";
    public static string $query = "q";
    public static string $sortBy = "sortby";
    public static string $display = "display";

    public static string $translation = 'translation';
    public static string $translationId = 'translationid';
    public static string $book = 'book';
    public static string $chapter = 'chapter';
    public static string $compare = 'compare';
    public static string $compareId = 'compareid';
    public static string $syndication = 'syndication';
    public static string $osisReference = 'osis';
}
