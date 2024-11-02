<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Entities;

class Audio
{
    /** @var string */
    public $reader;
    /** @var string */
    public $osis;
    /** @var string */
    public $mp3Url;
    /** @var string */
    public $oggUrl;
    /** @var int A unix timestamp. Indicates when the audio urls will stop working. */
    public $expiration;
    /** @var string A string representation of the audio url expiration timestamp. */
    public $expirationString;
}
