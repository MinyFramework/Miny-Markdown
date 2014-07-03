<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use Modules\Markdown\BlockFormatters\BlockQuoteFormatter;
use Modules\Markdown\BlockFormatters\CodeBlockFormatter;
use Modules\Markdown\BlockFormatters\HeadingFormatter;
use Modules\Markdown\BlockFormatters\HorizontalRuleFormatter;
use Modules\Markdown\BlockFormatters\ListFormatter;
use Modules\Markdown\BlockFormatters\ParagraphFormatter;
use Modules\Markdown\LineFormatters\StandardFormatters;
use OutOfBoundsException;

class Markdown
{
    /**
     * @var AbstractBlockFormatter[]
     */
    protected $blockFormatters = array();

    /**
     * @var AbstractLineFormatter[]
     */
    protected $lineFormatters = array();
    protected $links = array();
    protected $htmlBlocks = array();

    public function escape($str)
    {
        return addcslashes($str, '`*_{}[]()#+\'-.!');
    }

    public function unescape($str)
    {
        return strtr(
            $str,
            array(
                "\\'"  => "'",
                '\*'   => '*',
                '\_'   => '_',
                '\`'   => '`',
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
                '\!'   => '!',
                '\\\\' => '\\'
            )
        );
    }

    public function outdent($text)
    {
        return preg_replace('/^([ ]{1,4})/m', '', $text);
    }

    public function addLineFormatter(AbstractLineFormatter $formatter)
    {
        array_unshift($this->lineFormatters, $formatter);
    }

    public function addBlockFormatter(AbstractBlockFormatter $formatter)
    {
        array_unshift($this->blockFormatters, $formatter);
    }

    public function __construct()
    {
        $this->addLineFormatter(new StandardFormatters($this));

        $this->addBlockFormatter(new ParagraphFormatter($this));
        $this->addBlockFormatter(new BlockQuoteFormatter($this));
        $this->addBlockFormatter(new CodeBlockFormatter($this));
        $this->addBlockFormatter(new ListFormatter($this));
        $this->addBlockFormatter(new HorizontalRuleFormatter($this));
        $this->addBlockFormatter(new HeadingFormatter($this));
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

        return nl2br($line);
    }

    public function formatBlock($text)
    {
        foreach ($this->blockFormatters as $formatter) {
            $text = $formatter->format($text);
        }

        return $text;
    }

    public function hasHtml($key)
    {
        return isset($this->htmlBlocks[$key]);
    }

    public function getHtml($key)
    {
        if (!isset($this->htmlBlocks[$key])) {
            throw new OutOfBoundsException("HTML block \"{$key}\" is not found.");
        }

        list($opening, $content, $closing) = $this->htmlBlocks[$key];

        if ($opening === 'hr') {
            return $closing;
        }

        $lines = preg_split('/\n{2,}/', $content);
        foreach ($lines as &$line) {
            if ($this->hasHtml($line)) {
                $line = $this->getHtml($line);
            }
        }
        $content = implode("\n", $lines);

        return "<{$opening}>{$content}</{$closing}>";
    }

    public function storeHTMLBlock($matches)
    {
        $key                    = md5($matches[0]);
        $this->htmlBlocks[$key] = array($matches[2], $matches[3], $matches[1]);

        return "\n\n{$key}\n\n";
    }

    public function hashHTML($text)
    {
        $blockTags = 'p|div|h[1-6]|blockquote|pre|code|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del';

        $htmlPatterns = array(
            '#^<((' . $blockTags . ')(?:\b.*?)?)>(.*?)</\2>#sm',
            '#(?:(?<=\n\n)|\A\n?)([ ]{0,3}<(hr)\b([^<>])*?/?>[ \t]*(?=\n{2,}|\Z))#m',
            '#(?:(?<=\n\n)|\A\n?)([ ]{0,3}(?s:<!(--.*?--\s*)+>)[ \t]*(?=\n{2,}|\Z))#m'
        );

        foreach ($htmlPatterns as $pattern) {
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
        $text = preg_replace('/^\s*$/m', '', $text);
        $text = $this->hashHTML($text);

        foreach ($this->lineFormatters as $formatter) {
            $text = $formatter->prepare($text);
        }

        return $text;
    }

    public function format($text)
    {
        $this->links      = array();
        $this->htmlBlocks = array();
        $text             = $this->prepare($text);
        $formatted        = $this->formatBlock($text);

        return $this->unescape($formatted);
    }
}
