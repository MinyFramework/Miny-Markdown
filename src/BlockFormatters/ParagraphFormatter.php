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

class ParagraphFormatter extends AbstractBlockFormatter
{

    public function format($text)
    {
        $markdown = $this->getFormatter();
        $text = $markdown->hashHTML($text);

        $text  = preg_replace('/\\A\n+/', '', $text);
        $text  = preg_replace('/\n+\\z/', '', $text);
        $lines = preg_split('/\n{2,}/', $text);
        foreach ($lines as &$line) {
            if (!$markdown->hasHtml($line)) {
                $line = $markdown->formatLine($line) . '</p>';
                $line = preg_replace('/^([ \t]*)/u', '<p>', $line);
            } else {
                $line = $markdown->getHtml($line);
            }
        }

        return implode("\n\n", $lines);
    }
}
