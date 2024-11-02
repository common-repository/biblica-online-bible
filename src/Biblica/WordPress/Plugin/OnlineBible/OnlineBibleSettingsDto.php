<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\WordPress\Plugin\Common\Message;

class OnlineBibleSettingsDto
{
    public array $options;
    public array $tabs;
    public string $currentTabId;
    public string $form;
    /** @var Message[] */
    public array $messages = [];
}
