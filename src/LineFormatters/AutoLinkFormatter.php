<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown\LineFormatters;

use Modules\Markdown\MarkdownUtils;

class AutoLinkFormatter
{

    public function getPattern()
    {
        return '/(?<!\\\)<((?:http|https|ftp):\/\/.*?)(?<!\\\)>/u';
    }

    public function format($matches)
    {
        return sprintf('<a href="%s">%s</a>', MarkdownUtils::escape($matches[1]), $matches[1]);
    }
}
