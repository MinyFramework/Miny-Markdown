<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use Miny\Application\BaseApplication;
use Miny\Factory\Container;

class Module extends \Miny\Modules\Module
{

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();
        $this->ifModule(
            'Templating',
            function () use ($container) {
                $container->addCallback(
                    '\\Modules\\Templating\\Environment',
                    function (Environment $environment, Container $container) {
                        $environment->addFunction(
                            new \Modules\Templating\Compiler\Functions\CallbackFunction('t', array(
                                $container->get(__NAMESPACE__ . '\\Markdown', 'format', array('is_safe' => true))
                            ))
                        );
                    }
                );

            }
        );
    }
}
