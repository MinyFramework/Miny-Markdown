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
        $out = '';
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

    public static function insertCode($matches)
    {
        return '<code>' . self::escape(htmlspecialchars($matches[2])) . '</code>';
    }

    public static function insertEmail($matches)
    {
        $mail = self::randomize($matches[1]);
        $mailto = self::randomize('mailto:' . $matches[1]);
        return sprintf('<a href="%s">%s</a>', $mailto, $mail);
    }

    public static function insertLink($matches)
    {
        if (isset($matches[3])) {
            return sprintf('<a href="%s" title="%s">%s</a>', self::escape($matches[2]), self::escape($matches[3]),
                            $matches[1]);
        } else {
            if (isset($matches[2])) {
                $href = self::escape($matches[2]);
            } else {
                $href = self::escape($matches[1]);
            }
            return sprintf('<a href="%s">%s</a>', $href, $matches[1]);
        }
    }

    public static function insertImage($matches)
    {
        $matches = array_map('self::escape', $matches);
        if (isset($matches[3])) {
            return sprintf('<img src="%s" title="%s" alt="%s" />', $matches[2], $matches[3], $matches[1]);
        } else {
            return sprintf('<img src="%s" alt="%s" />', $matches[2], $matches[1]);
        }
    }

}