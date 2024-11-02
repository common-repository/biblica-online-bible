<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

class Message
{
    private string $text;
    private string $type;
    private bool $isDismissible;

    public const TYPE_ERROR = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_NOTICE = 'notice';

    public function __construct(string $text, string $type = Message::TYPE_NOTICE, bool $isDismissible = true)
    {
        $this->text = $text;
        $this->type = $type;
        $this->isDismissible = $isDismissible;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isDismissible(): bool
    {
        return $this->isDismissible;
    }
}
