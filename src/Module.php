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
use Minty\Environment;
use Modules\Markdown\LineFormatters\YoutubeFormatter;

class Module extends \Miny\Modules\Module
{

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();

        $container->addCallback(
            '\\Modules\\Markdown\\Markdown',
            function (Markdown $markdown) {
                $markdown->addLineFormatter(new YoutubeFormatter($markdown));
            }
        );

        $this->ifModule(
            'Cache',
            function () use ($container) {
                $container->addAlias(
                    '\\Modules\\Markdown\\Markdown',
                    '\\Modules\\Markdown\\CachedMarkdown'
                );
            }
        );

        $this->ifModule(
            'Templating',
            function () use ($container) {
                $container->addCallback(
                    '\\Minty\\Environment',
                    function (Environment $environment, Container $container) {
                        $environment->addExtension(
                            new TemplateExtension(
                                $container->get(__NAMESPACE__ . '\\Markdown')
                            )
                        );
                    }
                );
            }
        );
    }
}
