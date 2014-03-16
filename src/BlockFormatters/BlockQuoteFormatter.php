<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown\BlockFormatters;

use Modules\Markdown\AbstractBlockFormatter;

class BlockQuoteFormatter extends AbstractBlockFormatter
{

    private function trimBlockQuotePre($matches)
    {
        return preg_replace('/^  /m', '', $matches[0]);
    }

    private function transformBlockQuotesCallback($matches)
    {
        $matches[1] = preg_replace('/^[ ]*>[ ]?/m', '', $matches[1]);
        $matches[1] = '  ' . $matches[1];
        $matches[1] = preg_replace_callback(
            '#\s*<pre>.+?</pre>#s',
            array($this, 'trimBlockQuotePre'),
            $matches[1]
        );

        return sprintf("<blockquote>\n%s\n</blockquote>\n\n", $matches[1]);
    }

    public function format($text)
    {
        return preg_replace_callback(
            '/((^[ ]*>[ ]?.+\n(.+\n)*(?:\n)*)+)/mu',
            array($this, 'transformBlockQuotesCallback'),
            $text
        );
    }
}
