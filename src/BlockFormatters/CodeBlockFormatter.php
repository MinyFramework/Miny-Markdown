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
    private function transformCodeBlocksCallback($matches)
    {
        $formatter = $this->getFormatter();

        $code_html  = "\n\n<code><pre>%s\n</pre></code>\n\n";
        $matches[1] = $formatter->escape($formatter->outdent($matches[1]));
        $matches[1] = ltrim($matches[1], "\n");
        $matches[1] = rtrim($matches[1]);
        $matches[1] = sprintf($code_html, $matches[1]);

        return $matches[1];
    }

    public function format($text)
    {
        $code_block_pattern = '/(?:\n\n|\A)((?:(?:[ ]{4}).*\n*)+)((?=^[ ]{0,4}\S)|$)/mu';

        return preg_replace_callback(
            $code_block_pattern,
            array($this, 'transformCodeBlocksCallback'),
            $text
        );
    }
}
