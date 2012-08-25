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
    public $thumbnail_script;
    public $dir;

    public function __construct($dir, $thumbnail_script, $pattern = NULL)
    {
        $this->thumbnail_script = $thumbnail_script;
        $this->dir = $dir;
        if (!is_null($pattern)) {
            $this->pattern = $pattern;
        }
    }

    private function insertThumbnail($matches)
    {
        $dir = Markdown::escape($this->dir);
        $url = Markdown::escape($matches[2]);
        $label = Markdown::escape($matches[1]);
        return '<a href="' . $dir . $url
                . '" class="thumbnail">![' . $label
                . '](' . $this->dir . $this->thumbnail_script . $matches[2]
                . ')<span>' . $label . '</span></a>';
    }

    public function format($text)
    {
        return preg_replace_callback('/(?<!\\\)!\[thumbnail:(.+?)\]\((.+?)\)/u', array($this, 'insertThumbnail'), $text);
    }

}
