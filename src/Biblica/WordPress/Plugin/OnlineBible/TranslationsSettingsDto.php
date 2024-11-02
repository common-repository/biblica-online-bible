<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\WordPress\Plugin\Common\DropDownItem;

class TranslationsSettingsDto
{
    public string $settingsFields;
    /** @var DropDownItem[] */
    public array $translationDropDownItems;
    public array $translationInformation;
}
