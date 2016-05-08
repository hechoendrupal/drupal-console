<?php

namespace Drupal\Console\Annotations;

use Doctrine\Common\Annotations\FileCacheReader;
use Drupal\Console\Config;

class CacheFactory
{
    public static function createFileCache($reader, Config $config)
    {
        return new FileCacheReader(
            $reader,
            $config->getUserHomeDir() . '/.console/cache/annotations',
            false
        );
    }
}
