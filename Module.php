<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Formatter;

use Miny\Application\Application;

class Module extends \Miny\Application\Module
{
    public function init(Application $app)
    {
        $app->add('formatter', __NAMESPACE__ . '\FormatterBase');
        $app->add('markdown', __NAMESPACE__ . '\Markdown');
        $app->add('thumbnail', __NAMESPACE__ . '\Thumbnail');

        $app->getBlueprint('view')
                ->addMethodCall('addMethod', 'format', '*formatter::format')
                ->addMethodCall('addMethod', 'markdown', '*markdown::format');
    }

}
