<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use InvalidArgumentException;
use Modules\Cache\AbstractCacheDriver;
use Modules\Markdown\LineFormatters\StandardFormatters;

class Markdown
{
    /**
     * @var AbstractCacheDriver
     */
    private $cache;
    protected $block_formatters = array();

    /**
     * @var AbstractMarkdownLineFormatter[]
     */
    protected $formatters = array();
    protected $links = array();
    protected $html_blocks = array();

    public function addLineFormatter(AbstractMarkdownLineFormatter $formatter)
    {
        array_unshift($this->formatters, $formatter);
    }

    public function addBlockFormatter($formatter)
    {
        if (!is_callable($formatter)) {
            throw new InvalidArgumentException('Block formatter must be callable.');
        }
        $this->block_formatters[] = $formatter;
    }

    public function formatLine($line)
    {
        foreach ($this->formatters as $formatter) {
            $line = preg_replace_callback(
                $formatter->getPattern(),
                array($formatter, 'format'),
                $line
            );
        }

        return str_replace("\n", '<br />', $line);
    }

    public function __construct(AbstractCacheDriver $cache = null)
    {
        $this->cache = $cache;

        $this->addLineFormatter(new StandardFormatters());

        $this->block_formatters = array(
            array($this, 'transformLists'),
            array($this, 'transformCodeBlocks'),
            array($this, 'transformBlockQuotes'),
        );
    }


    private function prepare($text)
    {
        $arr  = array(
            "\r\n" => "\n",
            "\r"   => "\n",
            "\t"   => '    ',
        );
        $text = strtr($text, $arr);
        $text = preg_replace('/^\s*$/mu', '', $text);
        $text = $this->hashHTML($text);

        foreach ($this->formatters as $formatter) {
            $text = $formatter->prepare($text);
        }

        return $text;
    }

    private function callbackHeader($str, $level)
    {
        return sprintf('<h%2$d>%1$s</h%2$d>' . "\n\n", $this->formatLine($str), $level);
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

    private function transformHeaders($text)
    {
        $text = preg_replace_callback(
            '/^(.+)[ ]*\n(=|-)+[ ]*\n+/mu',
            array($this, 'callbackInsertSetexHeader'),
            $text
        );

        return preg_replace_callback(
            '/^(#{1,6})\s*(.+?)\s*#*\n+/mu',
            array($this, 'callbackInsertHeader'),
            $text
        );
    }

    private function transformHorizontalRules($text)
    {
        $hr_patterns = '\*|_|-';
        $hr_pattern  = '/^[ ]{0,2}([ ]?' . $hr_patterns . '[ ]?){3,}\s*$/';

        return preg_replace($hr_pattern, "<hr />\n", $text);
    }

    private function transformLists($text)
    {
        $lists_pattern = '/^(([ ]{0,3}((?:[*+-]|\d+[.]))[ ]+)(?s:.+?)(\z|\n{2,}(?=\S)(?![ ]*(?:[*+-]|\d+[.])[ ]+)))/mu';

        return preg_replace_callback($lists_pattern, array($this, 'transformListsCallback'), $text);
    }

    private function transformListsCallback($matches)
    {
        $list = preg_replace('/\n{2,}/', "\n\n\n", $matches[1]);
        $list = preg_replace('/\n{2,}$/', "\n", $list);
        $list = preg_replace_callback(
            '/(\n)?(^[ ]*)([*+-]|\d+[.])[ ]+((?s:.+?)(?:\z|\n{1,2}))(?=\n*(?:\z|\2([*+-]|\d+[.])[ ]+))/mu',
            array($this, 'processListItemsCallback'),
            $list
        );

        if (in_array($matches[3], array('*', '+', '-'))) {
            $pattern = "<ul>%s</ul>\n";
        } else {
            $pattern = "<ol>%s</ol>\n";
        }

        return sprintf($pattern, $list);
    }

    private function processListItemsCallback($matches)
    {
        $item         = $matches[4];
        $leading_line = $matches[1];
        if ($leading_line || (strpos($item, "\n\n") !== false)) {
            $item = $this->formatBlock(MarkdownUtils::outdent($item));
        } else {
            $item = $this->transformLists(MarkdownUtils::outdent($item));
            $item = $this->formatLine(rtrim($item));
        }

        return sprintf("<li>%s</li>\n", $item);
    }

    private function transformCodeBlocksCallback($matches)
    {
        $code_html  = "\n\n<code><pre>%s\n</pre></code>\n\n";
        $matches[1] = MarkdownUtils::escape(MarkdownUtils::outdent($matches[1]));
        $matches[1] = ltrim($matches[1], "\n");
        $matches[1] = rtrim($matches[1]);
        $matches[1] = sprintf($code_html, $matches[1]);

        return $matches[1];
    }

    private function transformCodeBlocks($text)
    {
        $code_block_pattern = '/(?:\n\n|\A)((?:(?:[ ]{4}).*\n*)+)((?=^[ ]{0,4}\S)|$)/mu';

        return preg_replace_callback(
            $code_block_pattern,
            array($this, 'transformCodeBlocksCallback'),
            $text
        );
    }

    private function trimBlockQuotePre($matches)
    {
        return preg_replace('/^  /m', '', $matches[0]);
    }

    private function transformBlockQuotesCallback($matches)
    {
        $matches[1] = preg_replace('/^[ ]*>[ ]?/', '', $matches[1]);
        $matches[1] = '  ' . $matches[1];
        $matches[1] = preg_replace_callback(
            '#\s*<pre>.+?</pre>#s',
            array($this, 'trimBlockQuotePre'),
            $matches[1]
        );

        return sprintf("<blockquote>\n%s\n</blockquote>\n\n", $matches[1]);
    }

    private function transformBlockQuotes($text)
    {
        $block_quote_pattern = '/((^[ ]*>[ ]?.+\n(.+\n)*(?:\n)*)+)/mu';

        return preg_replace_callback(
            $block_quote_pattern,
            array($this, 'transformBlockQuotesCallback'),
            $text
        );
    }

    private function makeParagraphs($text)
    {
        $text  = preg_replace('/\\A\n+/', '', $text);
        $text  = preg_replace('/\n+\\z/', '', $text);
        $lines = preg_split('/\n{2,}/', $text);
        foreach ($lines as &$line) {
            if (!isset($this->html_blocks[$line])) {
                $line = $this->formatLine($line) . '</p>';
                $line = preg_replace('/^([ \t]*)/u', '<p>', $line);
            } else {
                $line = $this->html_blocks[$line];
            }
        }

        return implode("\n\n", $lines);
    }

    private function storeHTMLBlock($matches)
    {
        $key                     = md5($matches[1]);
        $this->html_blocks[$key] = $matches[1];

        return "\n\n" . $key . "\n\n";
    }

    private function hashHTML($text)
    {
        $block_tags_a = 'p|div|h[1-6]|blockquote|pre|code|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del';
        $block_tags_b = 'p|div|h[1-6]|blockquote|pre|code|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math';

        $html_patterns = array(
            '#(^<(' . $block_tags_a . ')\b(.*\n)*?</\2>[ \t]*(?=\n+|\Z))#mux',
            '#(^<(' . $block_tags_b . ')\b(.*\n)*?.*</\2>[ \t]*(?=\n+|\Z))#mux',
            '#(?:(?<=\n\n)|\A\n?)([ ]{0,3}<(hr)\b([^<>])*?/?>[ \t]*(?=\n{2,}|\Z))#mux',
            '#(?:(?<=\n\n)|\A\n?)([ ]{0,3}(?s:<!(--.*?--\s*)+>)[ \t]*(?=\n{2,}|\Z))#mux'
        );

        foreach ($html_patterns as $pattern) {
            $text = preg_replace_callback($pattern, array($this, 'storeHTMLBlock'), $text);
        }

        return $text;
    }

    private function formatBlock($text)
    {
        $text = $this->transformHeaders($text);
        $text = $this->transformHorizontalRules($text);

        foreach ($this->block_formatters as $formatter) {
            $text = $formatter($text);
        }

        $text = $this->hashHTML($text);

        return $this->makeParagraphs($text);
    }

    public function format($text)
    {
        if ($this->cache !== null) {
            $cache_key = sha1($text);
            if ($this->cache->has($cache_key)) {
                return $this->cache->get($cache_key);
            }
        }
        $this->links       = array();
        $this->html_blocks = array();
        $text              = $this->prepare($text);
        $formatted         = $this->formatBlock($text);
        $unescaped         = MarkdownUtils::unescape($formatted);
        if ($this->cache !== null) {
            $this->cache->store($cache_key, $unescaped, 24 * 60 * 60 * 7 * 52);
        }

        return $unescaped;
    }
}
