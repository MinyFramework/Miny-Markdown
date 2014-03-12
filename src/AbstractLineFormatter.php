<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

abstract class AbstractLineFormatter extends AbstractBlockFormatter
{
    public function prepare($text)
    {
        return $text;
    }

    abstract public function getPattern();
}
