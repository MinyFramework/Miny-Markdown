<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Markdown;

use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Extension;

class TemplateExtension extends Extension
{
    private $markdown;

    public function __construct(Markdown $markdown)
    {
        $this->markdown = $markdown;
    }

    public function getExtensionName()
    {
        return 'markdown';
    }

    public function getFunctions()
    {
        $functions = array(
            new MethodFunction('markdown', 'format', array('is_safe' => true)),
            new MethodFunction('md', 'format', array('is_safe' => true))
        );

        return $functions;
    }

    public function format($text)
    {
        return $this->markdown->format($text);
    }

}
