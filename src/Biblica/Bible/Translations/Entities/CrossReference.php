<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

namespace Biblica\Bible\Translations\Entities;

class CrossReference
{
    /** @var string The reference that the content can link to. */
    public $id;
    /** @var string The reference in the content that this reference can link to. */
    public $referenceId;
    /** @var string The verse that the reference refers to, e.g. John 1:5. */
    public $verse;
    /** @var string May contain formatted HTML. */
    public $content;
    /** @var string The Osis that can be uses to get the cross referenced passage(s). */
    public $osis;
}
