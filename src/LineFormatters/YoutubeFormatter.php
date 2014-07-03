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

class YoutubeFormatter extends AbstractLineFormatter
{

    public function getPattern()
    {
        return '/(?<!\\\)\[youtube\]\((.+?)(?<!\\\)\)/';
    }

    public function format($matches)
    {
        $pattern = '<div class="youtubeWrapper"><iframe class="youtube" src="http://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe></div>';

        return sprintf($pattern, $matches[1]);
    }
}
