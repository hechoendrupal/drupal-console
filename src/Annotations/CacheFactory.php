<?php

namespace Drupal\Console\Annotations;

use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Annotations\Reader;
use Drupal\Console\Utils\ConfigurationManager;

class CacheFactory
{
    public static function createFileCache(
        Reader $reader,
        ConfigurationManager $configurationManager
    ) {
        return new FileCacheReader(
            $reader,
            $configurationManager->getHomeDirectory() . '/.console/cache/annotations',
            false
        );
    }
}
