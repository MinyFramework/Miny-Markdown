<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown\BlockFormatters;

use Modules\Markdown\AbstractBlockFormatter;

class HorizontalRuleFormatter extends AbstractBlockFormatter
{

    public function format($text)
    {
        $hr_pattern  = '/^[ ]{0,2}([*_-])(?>[ ]{0,2}\1){2,}\s*$/m';

        return preg_replace($hr_pattern, "<hr />\n", $text);
    }
}
