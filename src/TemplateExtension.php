<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Markdown;

use Minty\Compiler\TemplateFunction;
use Minty\Extension;

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
        $callback  = array($this->markdown, 'format');
        $functions = array(
            new TemplateFunction('markdown', $callback, array('is_safe' => true)),
            new TemplateFunction('md', $callback, array('is_safe' => true))
        );

        return $functions;
    }

}
