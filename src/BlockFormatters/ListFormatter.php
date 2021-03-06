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

class ListFormatter extends AbstractBlockFormatter
{

    private function transformListsCallback($matches)
    {
        $list = preg_replace('/\n{2,}/', "\n\n\n", $matches[1]);
        $list = preg_replace('/\n{2,}$/', "\n", $list);
        $list = preg_replace_callback(
            '/(\n)?(^[ ]*)([*+-]|\d+[.])[ ]+((?s:.+?)(?:\z|\n{1,2}))(?=\n*(?:\z|\2([*+-]|\d+[.])[ ]+))/m',
            array($this, 'processListItemsCallback'),
            $list
        );

        if (in_array($matches[3], array('*', '+', '-'))) {
            return "<ul>\n{$list}\n</ul>\n";
        }

        return "<ol>\n{$list}\n</ol>\n";
    }

    private function processListItemsCallback($matches)
    {
        $item        = $matches[4];
        $leadingLine = $matches[1];

        $markdown = $this->getFormatter();

        if ($leadingLine || (strpos($item, "\n\n") !== false)) {
            $item = $markdown->formatBlock($markdown->outdent($item));
        } else {
            $item = $this->format($markdown->outdent($item));
            $item = $markdown->formatLine(rtrim($item));
        }

        $item = $this->getFormatter()->hashHTML($item);

        return "<li>{$item}</li>\n";
    }


    public function format($text)
    {
        $listsPattern = '/^(([ ]{0,3}((?:[*+-]|\d+[.]))[ ]+)(?s:.+?)(\z|\n{2,}(?=\S)(?![ ]*(?:[*+-]|\d+[.])[ ]+)))/m';

        return preg_replace_callback($listsPattern, array($this, 'transformListsCallback'), $text);
    }
}
