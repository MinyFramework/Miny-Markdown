<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

abstract class AbstractBlockFormatter
{
    private $markdown;

    public function __construct(Markdown $markdown) {
        $this->markdown = $markdown;
    }

    /**
     * @return mixed
     */
    public function getFormatter()
    {
        return $this->markdown;
    }

    abstract public function format($text);
}
