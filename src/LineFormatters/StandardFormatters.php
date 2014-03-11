<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown\LineFormatters;

use Modules\Markdown\AbstractMarkdownLineFormatter;
use Modules\Markdown\MarkdownUtils;

class StandardFormatters extends AbstractMarkdownLineFormatter
{
    private $formatters;
    private $links;

    public function __construct()
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
        ); //url
        if (isset($matches[3])) {
            $arr[3] = str_replace('"', '&quot;', $matches[3]); //title
        }
        $this->links[$matches[1]] = $arr;

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
        (`+)(.*?)(?<!\\\)\1                                 # code              1, 2
        |!\[(.+?)(?<!\\\)\]\((.+?)(?:|\s+"(.*?)")(?<!\\\)\) # image             3, 4, 5
        |!\[(.*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[(.*?)(?<!\\\)\] # image definition  6, 7
        |\[(.+?)(?<!\\\)\]\((.+?)(?:|\s+"(.*?)")(?<!\\\)\)  # link              8, 9, 10
        |\[(.*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[(.*?)(?<!\\\)\]  # link definition   11, 12
        |<((?:http|https|ftp):\/\/.*?)(?<!\\\)>             # auto link         13
        |<(\w+@(?:\w+[.])*\w+)>                             # auto email        14
        |(\*\*|__)(.+?)(?<!\\\)\15                          # bold              15, 16
        |(\*|_)(.+?)(?<!\\\)\17                             # italic            17, 18
        )/xu';
    }

    public function formatCode($matches, $base)
    {
        return '<code>' . MarkdownUtils::escape(htmlspecialchars($matches[$base + 1])) . '</code>';
    }

    public function formatImage($matches, $base)
    {
        $matches = array_map(__NAMESPACE__ . '\\MarkdownUtils::escape', $matches);
        if (isset($matches[$base + 2])) {
            return sprintf(
                '<img src="%s" title="%s" alt="%s" />',
                $matches[$base + 1],
                $matches[$base + 2],
                $matches[$base]
            );
        } else {
            return sprintf('<img src="%s" alt="%s" />', $matches[$base + 1], $matches[$base]);
        }
    }

    public function formatImageDefinition($matches, $base)
    {
        if (isset($matches[$base + 1])) {
            if (isset($this->links[$matches[$base]])) {
                $link = $this->links[$matches[$base]];
            }
        } elseif (isset($this->links[$matches[$base + 1]])) {
            $link = $this->links[$matches[$base + 1]];
        }

        if (!isset($link)) {
            //not a definition
            return $matches[0];
        }
        $link[1] = $matches[$base];

        return $this->formatImage($link, 0);
    }

    public function formatLink($matches, $base)
    {
        if (isset($matches[$base + 2])) {
            return sprintf(
                '<a href="%s" title="%s">%s</a>',
                MarkdownUtils::escape($matches[$base + 1]),
                MarkdownUtils::escape($matches[$base + 2]),
                $matches[1]
            );
        } else {
            if ($matches[$base + 1] !== '') {
                $href = MarkdownUtils::escape($matches[$base + 1]);
            } else {
                $href = MarkdownUtils::escape($matches[$base]);
            }

            return sprintf('<a href="%s">%s</a>', $href, $matches[$base]);
        }
    }

    public function formatLinkDefinition($matches, $base)
    {
        if ($matches[$base + 1] !== '') {
            if (isset($this->links[$matches[$base]])) {
                $link = $this->links[$matches[$base]];
            }
        } elseif (isset($this->links[$matches[$base + 1]])) {
            $link = $this->links[$matches[$base + 1]];
        }
        if (!isset($link)) {
            //not a definition
            return $matches[0];
        }
        $link[1] = $matches[$base];

        return MarkdownUtils::insertLink($link);
    }

    public function formatAutoLink($matches, $base)
    {
        return sprintf(
            '<a href="%s">%s</a>',
            MarkdownUtils::escape($matches[$base]),
            $matches[$base]
        );
    }

    public function formatAutoEmail($matches, $base)
    {
        $mail   = MarkdownUtils::randomize($matches[$base]);
        $mailTo = MarkdownUtils::randomize('mailto:' . $matches[$base]);

        return sprintf('<a href="%s">%s</a>', $mailTo, $mail);
    }

    public function formatBold($matches, $base)
    {
        return sprintf('<strong>%s</strong>', $matches[$base + 1]);
    }

    public function formatItalic($matches, $base)
    {
        return sprintf('<em>%s</em>', $matches[$base + 1]);
    }

    public function format($matches)
    {
        for ($i = 1; '' === $matches[$i]; ++$i) {
            ;
        }

        return $this->formatters[$i]($matches, $i);
    }
}
