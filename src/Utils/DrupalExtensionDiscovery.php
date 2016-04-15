<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\DrupalExtensionDiscovery.
 */

namespace Drupal\Console\Utils;

use Drupal\Core\Extension\ExtensionDiscovery;

class DrupalExtensionDiscovery extends ExtensionDiscovery
{
    /**
     * Reset internal static cache.
     */
    public static function reset()
    {
        static::$files = array();
    }
}
