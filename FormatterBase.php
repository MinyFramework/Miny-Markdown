<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Formatter;

use Modules\Cache\iCacheDriver;

class FormatterBase
{
    private $formatters = array();
    private $cache;

    public function __construct(iCacheDriver $driver = NULL)
    {
        $this->cache = $driver;
    }

    public function addFormatter(iFormatter $formatter)
    {
        $this->formatters[] = $formatter;
    }

    private function doFormat($text)
    {
        foreach ($this->formatters as $formatter) {
            $text = $formatter->format($text);
        }
        return $text;
    }

    public function format($text)
    {
        if (!is_null($this->cache)) {
            $key = md5($text);
            if (!$this->cache->has($key)) {
                $text = $this->doFormat($text);
                $this->cache->store($key, $text, 3600);
            } else {
                $text = $this->cache->get($key);
            }
        } else {
            $text = $this->doFormat($text);
        }
        return $text;
    }

}
