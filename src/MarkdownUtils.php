<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Markdown;

class MarkdownUtils
{
    private static $char_map = array(
        '\\\\' => '\\',
        '\`'   => '`',
        '\*'   => '*',
        '\_'   => '_',
        '\{'   => '{',
        '\}'   => '}',
        '\['   => '[',
        '\]'   => ']',
        '\('   => '(',
        '\)'   => ')',
        '\#'   => '#',
        '\+'   => '+',
        '\-'   => '-',
        '\.'   => '.',
        '\!'   => '!'
    );

    public static function escape($str)
    {
        return strtr($str, array_flip(self::$char_map));
    }

    public static function unescape($str)
    {
        return strtr($str, self::$char_map);
    }

    public static function outdent($text)
    {
        return preg_replace('/^([ ]{1,4})/m', '', $text);
    }

    public static function randomize($str)
    {
        $out    = '';
        $strlen = strlen($str);
        for ($i = 0; $i < $strlen; $i++) {
            switch (rand(0, 2)) {
                case 0:
                    $out .= '&#' . ord($str[$i]) . ';';
                    break;
                case 1:
                    $out .= $str[$i];
                    break;
                case 2:
                    $out .= '&#x' . dechex(ord($str[$i])) . ';';
                    break;
            }
        }

        return $out;
    }

}
