<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use Modules\Cache\AbstractCacheDriver;
use Modules\Markdown\BlockFormatters\BlockQuoteFormatter;
use Modules\Markdown\BlockFormatters\CodeBlockFormatter;
use Modules\Markdown\BlockFormatters\HeadingFormatter;
use Modules\Markdown\BlockFormatters\HorizontalRuleFormatter;
use Modules\Markdown\BlockFormatters\ListFormatter;
use Modules\Markdown\BlockFormatters\ParagraphFormatter;
use Modules\Markdown\LineFormatters\StandardFormatters;

class Markdown
{
    /**
     * @var AbstractCacheDriver
     */
    private $cache;

    /**
     * @var AbstractBlockFormatter[]
     */
    protected $blockFormatters = array();

    /**
     * @var AbstractMarkdownLineFormatter[]
     */
    protected $lineFormatters = array();
    protected $links = array();
    protected $htmlBlocks = array();

    public function addLineFormatter(AbstractMarkdownLineFormatter $formatter)
    {
        array_unshift($this->lineFormatters, $formatter);
    }

    public function addBlockFormatter(AbstractBlockFormatter $formatter)
    {
        array_unshift($this->blockFormatters, $formatter);
    }

    public function __construct(AbstractCacheDriver $cache = null)
    {
        $this->cache = $cache;

        $this->addLineFormatter(new StandardFormatters());

        $this->addBlockFormatter(new ParagraphFormatter($this));
        $this->addBlockFormatter(new BlockQuoteFormatter($this));
        $this->addBlockFormatter(new CodeBlockFormatter($this));
        $this->addBlockFormatter(new ListFormatter($this));
        $this->addBlockFormatter(new HeadingFormatter($this));
        $this->addBlockFormatter(new HorizontalRuleFormatter($this));
    }

    public function formatLine($line)
    {
        foreach ($this->lineFormatters as $formatter) {
            $line = preg_replace_callback(
                $formatter->getPattern(),
                array($formatter, 'format'),
                $line
            );
        }

        return str_replace("\n", '<br />', $line);
    }

    public function formatBlock($text)
    {
        foreach ($this->blockFormatters as $formatter) {
            $text = $formatter->format($text);
        }

        return $text;
    }

    public function storeHTMLBlock($matches)
    {
        $key                     = md5($matches[1]);
        $this->htmlBlocks[$key] = $matches[1];

        return "\n\n" . $key . "\n\n";
    }

    public function hasHtml($key)
    {
        return isset($this->htmlBlocks[$key]);
    }

    public function getHtml($key)
    {
        if (!isset($this->htmlBlocks[$key])) {
            throw new \OutOfBoundsException(sprintf('HTML block "%s" is not found.', $key));
        }

        return $this->htmlBlocks[$key];
    }

    public function hashHTML($text)
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

        foreach ($this->lineFormatters as $formatter) {
            $text = $formatter->prepare($text);
        }

        return $text;
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
        $this->htmlBlocks = array();
        $text              = $this->prepare($text);
        $formatted         = $this->formatBlock($text);
        $unescaped         = MarkdownUtils::unescape($formatted);

        if ($this->cache !== null) {
            $this->cache->store($cache_key, $unescaped, 24 * 60 * 60 * 7 * 52);
        }

        return $unescaped;
    }
}
