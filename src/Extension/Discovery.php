<?php

/**
 * @file
 * Contains \Drupal\Console\Extension\Discovery.
 */

namespace Drupal\Console\Extension;

use Drupal\Core\Extension\ExtensionDiscovery;

/*
 * @see Remove DrupalExtensionDiscovery subclass once
 * https://www.drupal.org/node/2503927 is fixed.
 */
class Discovery extends ExtensionDiscovery
{
    /**
     * Reset internal static cache.
     */
    public static function reset()
    {
        static::$files = [];
    }
}
