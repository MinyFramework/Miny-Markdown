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
use Modules\Markdown\LineFormatters\AutoLinkFormatter;
use Modules\Markdown\LineFormatters\YoutubeFormatter;
use Modules\Templating\Environment;

class Module extends \Miny\Modules\Module
{

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();

        $container->addCallback('\\Modules\\Markdown\\Markdown', function(Markdown $markdown){
                $formatters = array(
                    'youtube' => new YoutubeFormatter(),
                    'autolink' => new AutoLinkFormatter()
                );

                foreach($formatters as $name => $formatter) {
                    $markdown->addLineFormatter($name, $formatter->getPattern(),
                        array($formatter, 'format'));
                }
            });

        $this->ifModule(
            'Templating',
            function () use ($container) {
                $container->addCallback(
                    '\\Modules\\Templating\\Environment',
                    function (Environment $environment, Container $container) {
                        $environment->addFunction(
                            new \Modules\Templating\Compiler\Functions\CallbackFunction('markdown', array(
                                    $container->get(__NAMESPACE__ . '\\Markdown'),
                                    'format'
                                ),
                                array('is_safe' => true)
                            )
                        );
                    }
                );

            }
        );
    }
}
