<?php

/**
 * @file
 * Contains \Drupal\Console\Extension\Discovery.
 */

namespace Drupal\Console\Extension;

use Drupal\Core\Extension\ExtensionDiscovery;

class Discovery extends ExtensionDiscovery
{
    /**
     * Reset internal static cache.
     */
    public static function reset()
    {
        static::$files = array();
    }
}
