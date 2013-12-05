<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use Miny\Application\BaseApplication;

class Module extends \Miny\Application\Module
{

    public function init(BaseApplication $app)
    {
        $app->add('markdown', __NAMESPACE__ . '\Markdown');

        $app->getBlueprint('view_helpers')
                ->addMethodCall('addMethod', 'markdown', '*markdown::format');

        $this->ifModule('Templating', function()use($app) {
            $app->add('markdown_function', '\Modules\Templating\Compiler\Functions\CallbackFunction')
                    ->setArguments('markdown', '*markdown::format', true);
            $app->getBlueprint('template_environment')
                    ->addMethodCall('addFunction', '&markdown_function');
        });
    }
}
