<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Formatter;

class Thumbnail implements iFormatter
{
    private $pattern = '<a href="%1$s$2" class="thumbnail">![$1](%1$s%2$s$2)<span>$1</span></a>';
    private $thumbnail_script;
    private $dir;

    public function __construct($dir, $thumbnail_script, $pattern = NULL)
    {
        $this->thumbnail_script = $thumbnail_script;
        $this->dir = $dir;
        if (!is_null($pattern)) {
            $this->pattern = $pattern;
        }
    }

    public function format($text)
    {
        $replace = sprintf($this->pattern, $this->dir, $this->thumbnail_script);
        return preg_replace('/(?<!\\\)!\[thumbnail:(.+?)\]\((.+?)\)/u', $replace, $text);
    }

}
