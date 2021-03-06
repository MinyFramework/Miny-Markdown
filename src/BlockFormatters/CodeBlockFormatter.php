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

class CodeBlockFormatter extends AbstractBlockFormatter
{

    public function format($text)
    {
        $codeBlockPattern = '/(?:\n\n|\A)((?:(?:[ ]{4}).*\n*)+)((?=^[ ]{0,4}\S)|$)/m';

        $formatter = $this->getFormatter();

        return preg_replace_callback(
            $codeBlockPattern,
            function ($matches) use ($formatter) {
                $text = $formatter->escape($formatter->outdent($matches[1]));
                $text = ltrim($text, "\n");
                $text = strtr(
                    rtrim($text),
                    array(
                        '&' => '&amp;',
                        '<' => '&lt;',
                        '>' => '&gt;'
                    )
                );

                return "\n\n<pre><code>{$text}\n</code></pre>\n\n";
            },
            $text
        );
    }
}
