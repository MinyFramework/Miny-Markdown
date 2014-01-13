<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use Miny\Application\BaseApplication;

class Module extends \Miny\Modules\Module
{

    public function init(BaseApplication $app)
    {
        $app->add('markdown', __NAMESPACE__ . '\Markdown');

        $this->ifModule('Cache', function()use($app) {
            $app->getBlueprint('markdown')
                    ->setArguments('&cache');
        });

        $this->ifModule('Templating', function()use($app) {
            $app->add('markdown_function', '\Modules\Templating\Compiler\Functions\CallbackFunction')
                    ->setArguments('markdown', '*markdown::format', array('is_safe' => true));
            $app->getBlueprint('template_environment')
                    ->addMethodCall('addFunction', '&markdown_function');
        });
    }
}
