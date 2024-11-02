<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

class GeneralSettingsDto
{
    public array $options;
    public string $settingsFields;
    public string $bibleReaderPageDropDown;
    public string $bibleSearchPageDropDown;
}
