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

class Markdown
{
    /**
     * @var iCacheDriver
     */
    private $cache;
    protected $line_formatters  = array();
    protected $block_formatters = array();
    protected $links            = array();
    protected $html_blocks      = array();

    private function escapeSpan($matches)
    {
        return MarkdownUtils::escape($matches[1]);
    }

    public function addLineFormatter($name, $pattern, $formatter)
    {
        if (!is_null($pattern)) {
            $this->line_formatter_patterns[$name] = $pattern;
        }
        if (!is_null($formatter)) {
            $this->line_formatters[$name] = $formatter;
        }
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
        foreach ($this->line_formatter_patterns as $name => $pattern) {
            $formatter = $this->line_formatters[$name];
            if (is_callable($formatter)) {
                if (is_null($pattern)) {
                    $line = $formatter($line);
                } else {
                    $line = preg_replace_callback($pattern, $formatter, $line);
                }
            } else if (is_callable(array($this, $formatter))) {
                $line = preg_replace_callback($pattern, array($this, $formatter), $line);
            } else if (is_string($formatter)) {
                $line = preg_replace($pattern, $formatter, $line);
            } else {
                throw new InvalidArgumentException('Formatter must be callable or string.');
            }
        }
        $line = str_replace("  \n", '<br />', $line);
        return $line;
    }

    public function __construct(AbstractCacheDriver $cache = NULL)
    {
        $this->cache = $cache;
        $patterns    = array(
            'code'             => '/(?<!\\\)(`+)(.*?)(?<!\\\)\1/u',
            'youtube'          => '/(?<!\\\)\[youtube\]\((.+?)(?<!\\\)\)/u',
            'image'            => '/(?<!\\\)!\[(.+?)(?<!\\\)\]\((.+?)(?:\s+"(.*?)")?(?<!\\\)\)/u',
            'image_definition' => '/(?<!\\\)!\[(.*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[(.*?)(?<!\\\)\]/u',
            'link'             => '/(?<!\\\)\[(.+?)(?<!\\\)\]\((.+?)(?:\s+"(.*?)")?(?<!\\\)\)/u',
            'link_definition'  => '/(?<!\\\)\[(.*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[(.*?)(?<!\\\)\]/u',
            'autoemail'        => '/(?<!\\\)<(\w+@(\w+[.])*\w+)>/u',
            'autolink'         => '/(?<!\\\)<((?:http|https|ftp):\/\/.*?)(?<!\\\)>/u',
            'bold'             => '/(?<!\\\)(\*\*|__)(.+?)(?<!\\\)\1/u',
            'itallic'          => '/(?<!\\\)(\*|_)(.+?)(?<!\\\)\1/u'
        );
        $formatters  = array(
            'code'             => __NAMESPACE__ . '\MarkdownUtils::insertCode',
            'image'            => __NAMESPACE__ . '\MarkdownUtils::insertImage',
            'image_definition' => array($this, 'insertImageDefinition'),
            'link'             => __NAMESPACE__ . '\MarkdownUtils::insertLink',
            'link_definition'  => array($this, 'insertLinkDefinition'),
            'autoemail'        => __NAMESPACE__ . '\MarkdownUtils::insertEmail',
            'autolink'         => __NAMESPACE__ . '\MarkdownUtils::insertLink',
            'bold'             => '<strong>$2</strong>',
            'itallic'          => '<em>$2</em>',
            'youtube'          => '<div class="youtubeWrapper"><iframe class="youtube" src="http://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe></div>'
        );
        foreach ($patterns as $name => $pattern) {
            $this->addLineFormatter($name, $pattern, $formatters[$name]);
        }

        $this->block_formatters = array(
            array($this, 'transformLists'),
            array($this, 'transformCodeBlocks'),
            array($this, 'transformBlockQuotes'),
        );
    }

    private function insertLinkDefinition($matches)
    {
        if (empty($matches[2])) {
            if (isset($this->links[$matches[1]])) {
                $link = $this->links[$matches[1]];
            }
        } elseif (isset($this->links[$matches[2]])) {
            $link = $this->links[$matches[2]];
        }
        if (!isset($link)) {
            //not a definition
            return $matches[0];
        }
        $link[1] = $matches[1];
        return MarkdownUtils::insertLink($link);
    }

    private function insertImageDefinition($matches)
    {
        if (empty($matches[2])) {
            if (isset($this->links[$matches[1]])) {
                $link = $this->links[$matches[1]];
            }
        } elseif (isset($this->links[$matches[2]])) {
            $link = $this->links[$matches[2]];
        }

        if (!isset($link)) {
            //not a definition
            return $matches[0];
        }
        $link[1] = $matches[1];
        return MarkdownUtils::insertImage($link);
    }

    private function collectLinkDefinition($matches)
    {
        $arr = array(
            2 => preg_replace(
                    array(
                '/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/',
                '#<(?![a-z/?\$!])#'
                    ), array(
                '&amp;',
                '&lt;'
                    ), $matches[2])); //url
        if (isset($matches[3])) {
            $arr[3] = str_replace('"', '&quot;', $matches[3]); //title
        }
        $this->links[$matches[1]] = $arr;
        return '';
    }

    private function prepare($text)
    {
        $arr  = array(
            "\r\n" => "\n",
            "\r"   => "\n",
            "\t"   => '    ',
        );
        $text = strtr($text, $arr);
        $text = preg_replace("/^\s*$/mu", '', $text);
        $text = $this->hashHTML($text);
        return preg_replace_callback('/^[ ]{0,3}\[(.*)\]:[ ]*\n?[ ]*<?(\S+?)>?[ ]*\n?[ ]*(?:(?<=\s)["(](.*?)[")][ ]*)?(?:\n+|\Z)/mu',
                array($this, 'collectLinkDefinition'), $text);
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
        $text = preg_replace_callback('/^(.+)[ ]*\n(=|-)+[ ]*\n+/mu', array($this, 'callbackInsertSetexHeader'), $text);
        return preg_replace_callback('/^(#{1,6})\s*(.+?)\s*#*\n+/mu', array($this, 'callbackInsertHeader'), $text);
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
        $callback      = array($this, 'transformListsCallback');
        return preg_replace_callback($lists_pattern, $callback, $text);
    }

    private function transformListsCallback($matches)
    {
        $list = preg_replace('/\n{2,}/', "\n\n\n", $matches[1]);
        $list = preg_replace('/\n{2,}$/', "\n", $list);
        $list = preg_replace_callback(
                '/(\n)?(^[ ]*)([*+-]|\d+[.])[ ]+((?s:.+?)(?:\z|\n{1,2}))(?=\n*(?:\z|\2([*+-]|\d+[.])[ ]+))/mu',
                array($this, 'processListItemsCallback'), $list);

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
        $callback           = array($this, 'transformCodeBlocksCallback');
        return preg_replace_callback($code_block_pattern, $callback, $text);
    }

    private function trimBlockQuotePre($matches)
    {
        return preg_replace('/^  /m', '', $matches[0]);
    }

    private function transformBlockQuotesCallback($matches)
    {
        $matches[1] = preg_replace('/^[ ]*>[ ]?/', '', $matches[1]);
        $matches[1] = '  ' . $matches[1];
        $matches[1] = preg_replace_callback('#\s*<pre>.+?</pre>#s', array($this, 'trimBlockQuotePre'), $matches[1]);
        return sprintf("<blockquote>\n%s\n</blockquote>\n\n", $matches[1]);
    }

    private function transformBlockQuotes($text)
    {
        $block_quote_pattern = '/((^[ ]*>[ ]?.+\n(.+\n)*(?:\n)*)+)/mu';
        $callback            = array($this, 'transformBlockQuotesCallback');
        return preg_replace_callback($block_quote_pattern, $callback, $text);
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
        $key                     = hash('md5', $matches[1]);
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

        $callback = array($this, 'storeHTMLBlock');

        foreach ($html_patterns as $pattern) {
            $text = preg_replace_callback($pattern, $callback, $text);
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
