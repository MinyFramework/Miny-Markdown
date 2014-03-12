<?php

/**
 * This file is part of the Miny framework.
 * This is the reimplementation of Markdown, originally written by John Gruber (http://daringfireball.net/)
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Markdown;

use Modules\Cache\AbstractCacheDriver;

class CachedMarkdown extends Markdown
{
    /**
     * @var AbstractCacheDriver
     */
    private $cache;

    public function __construct(AbstractCacheDriver $cache)
    {
        $this->cache = $cache;
        parent::__construct();
    }

    public function format($text)
    {
        $cacheKey = sha1($text);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $text = parent::format($text);
        $this->cache->store($cacheKey, $text, 24 * 60 * 60 * 7 * 52);

        return $text;
    }
}
