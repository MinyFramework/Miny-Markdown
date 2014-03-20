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

class HeadingFormatter extends AbstractBlockFormatter
{

    private function callbackHeader($str, $level)
    {
        $line = $this->getFormatter()->formatLine($str);

        return sprintf('<h%2$d>%1$s</h%2$d>' . "\n\n", $line, $level);
    }

    private function callbackInsertHeader($matches)
    {
        return $this->callbackHeader($matches[2], strlen($matches[1]));
    }

    private function callbackInsertSetexHeader($matches)
    {
        switch ($matches[2]) {
            case '=':
                $level = 1;
                break;
            case '-':
                $level = 2;
                break;
        }
        return $this->callbackHeader($matches[1], $level);
    }

    public function format($text)
    {
        $text = preg_replace_callback(
            '/^(.+?)[ ]*\n(=|-)+[ ]*\n+/m',
            array($this, 'callbackInsertSetexHeader'),
            $text
        );

        return preg_replace_callback(
            '/^(#{1,6})\s*(.+?)\s*#*\n+/m',
            array($this, 'callbackInsertHeader'),
            $text
        );
    }
}
