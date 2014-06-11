<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown\LineFormatters;

use Modules\Markdown\AbstractLineFormatter;
use Modules\Markdown\Markdown;

class StandardFormatters extends AbstractLineFormatter
{
    private $formatters;
    private $links;

    public function __construct(Markdown $markdown)
    {
        $this->formatters = array(
            1  => array($this, 'formatCode'),
            3  => array($this, 'formatImage'),
            6  => array($this, 'formatImageDefinition'),
            8  => array($this, 'formatLink'),
            11 => array($this, 'formatLinkDefinition'),
            13 => array($this, 'formatAutoLink'),
            14 => array($this, 'formatAutoEmail'),
            15 => array($this, 'formatBold'),
            17 => array($this, 'formatItalic'),
        );
        parent::__construct($markdown);
    }

    private function collectLinkDefinition($matches)
    {
        $arr = array(
            2 => preg_replace(
                array(
                    '/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/',
                    '#<(?![a-z/?\$!])#'
                ),
                array(
                    '&amp;',
                    '&lt;'
                ),
                $matches[2]
            )
        );
        //url
        if (isset($matches[3])) {
            $arr[3] = strtr($matches[3], '"', '&quot;'); //title
        }
        $this->links[strtolower($matches[1])] = $arr;

        return '';
    }

    public function prepare($text)
    {
        return preg_replace_callback(
            '/^[ ]{0,3}\[(.*)\]:[ ]*\n?[ ]*<?(\S+?)>?[ ]*\n?[ ]*(?:(?<=\s)["(](.*?)[")][ ]*)?(?:\n+|\Z)/mu',
            array($this, 'collectLinkDefinition'),
            $text
        );
    }


    public function getPattern()
    {
        return '/(?<!\\\)(?:
        (`+)((?:\\\\.|[^\1\\\\])*?)\1                               # code              1, 2
        |!\[([^\]]+?)(?<!\\\)\]\((.+?)(?:|\s+"(.*?)")(?<!\\\)\)     # image             3, 4, 5
        |!\[([^\]]*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[([^\]]*?)(?<!\\\)\] # image definition  6, 7
        |\[([^\]]+?)(?<!\\\)\]\((.+?)(?:|\s+"([^\]]*?)")(?<!\\\)\)  # link              8, 9, 10
        |\[([^\]]+?)(?<!\\\)\]\s{0,1}(?<!\\\)\[([^\]]*?)(?<!\\\)\]  # link definition   11, 12
        |<((?:http|https|ftp):\/\/.*?)(?<!\\\)>                     # auto link         13
        |<(\w+@(?:\w+[.])*\w+)>                                     # auto email        14
        |(\*\*|\_\_)((?:\\\\.|[^\15\\\\])*?)\15                     # bold              15, 16
        |(\*|\_)((?:\\\\.|[^\17\\\\])*?)\17                         # italic            17, 18
        )/xu';
    }

    public function formatCode($matches, $base)
    {
        $code = htmlspecialchars($matches[$base + 1]);

        return "<code>{$code}</code>";
    }

    public function formatImage($matches, $base)
    {
        $markdown = $this->getFormatter();
        $alt      = $markdown->escape($matches[$base]);
        $src      = $markdown->escape($matches[$base + 1]);
        if (isset($matches[$base + 2])) {
            $title = $markdown->escape($matches[$base + 2]);

            return "<img src=\"{$src}\" title=\"{$title}\" alt=\"{$alt}\" />";
        }

        return "<img src=\"{$src}\" alt=\"{$alt}\" />";
    }

    public function formatImageDefinition($matches, $base)
    {
        $definitionId = $matches[$base + 1] !== '' ? $matches[$base + 1] : $matches[$base];
        $definitionId = strtolower($definitionId);
        if (isset($this->links[$definitionId])) {
            $link    = $this->links[$definitionId];
            $link[1] = $matches[$base];

            return $this->formatImage($link, 1);
        }

        //not a definition
        return $matches[0];
    }

    public function formatLink($matches, $base)
    {
        $markdown = $this->getFormatter();
        $linkText = $matches[$base];
        $href     = $markdown->escape($matches[$base + 1]);
        if (isset($matches[$base + 2])) {
            $title = $markdown->escape($matches[$base + 2]);

            return "<a href=\"{$href}\" title=\"{$title}\">{$linkText}</a>";
        }

        return "<a href=\"{$href}\">{$linkText}</a>";
    }

    public function formatLinkDefinition($matches, $base)
    {
        $definitionId = $matches[$base + 1] !== '' ? $matches[$base + 1] : $matches[$base];
        $definitionId = strtolower($definitionId);
        if (isset($this->links[$definitionId])) {
            $link    = $this->links[$definitionId];
            $link[1] = $matches[$base];

            return $this->formatLink($link, 1);
        }

        //not a definition
        return $matches[0];
    }

    public function formatAutoLink($matches, $base)
    {
        $href = $this->getFormatter()->escape($matches[$base]);

        return "<a href=\"{$href}\">{$matches[$base]}</a>";
    }

    public function formatAutoEmail($matches, $base)
    {
        $mail   = $this->randomize($matches[$base]);
        $mailTo = $this->randomize('mailto:' . $matches[$base]);

        return "<a href=\"{$mailTo}\">{$mail}</a>";
    }

    public function formatBold($matches, $base)
    {
        return "<strong>{$matches[$base + 1]}</strong>";
    }

    public function formatItalic($matches, $base)
    {
        return "<em>{$matches[$base + 1]}</em>";
    }

    private function randomize($str)
    {
        $out    = '';
        $strLen = strlen($str);
        for ($i = 0; $i < $strLen; $i++) {
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

    public function format($matches)
    {
        for ($i = 1; '' === $matches[$i]; ++$i) ;

        return call_user_func($this->formatters[$i], $matches, $i);
    }
}
