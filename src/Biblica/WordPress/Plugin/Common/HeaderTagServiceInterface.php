<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

interface HeaderTagServiceInterface
{
    public function setPage(SeoPageInterface $seoPage);
    public function addTag(string $tag): void;
    public function renderTags(): string;
    public function insertTags(): void;
}
