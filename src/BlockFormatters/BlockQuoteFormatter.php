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

    public function format($text)
    {
        $formatter = $this->getFormatter();

        return preg_replace_callback(
            '/((^[ ]*>[ ]?.+\n(.+\n)*(?:\n)*)+)/mu',
            function ($matches) use ($formatter) {

                // trim one level of quoting and empty lines
                $text = preg_replace('/^[ ]*>[ ]?/m', '', $matches[1]);

                // recursion to catch e.g. nested quotes
                $text = $formatter->formatBlock($text);
                $text = $formatter->hashHTML($text);

                return "<blockquote>\n{$text}\n</blockquote>\n\n";
            },
            $text
        );
    }
}
