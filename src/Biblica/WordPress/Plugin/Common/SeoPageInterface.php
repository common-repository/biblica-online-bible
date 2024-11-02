<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

interface SeoPageInterface
{
    public function getTitle(): string;
    public function getHeading(): string;
    public function getDescription(): string;
    public function getCanonicalUrl(): string;
}
