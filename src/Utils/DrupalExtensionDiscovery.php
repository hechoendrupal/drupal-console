<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Utils\DrupalExtensionDiscovery.
 */

namespace Drupal\AppConsole\Utils;

use Drupal\Core\Extension\ExtensionDiscovery;

class DrupalExtensionDiscovery extends ExtensionDiscovery {

  /**
   * Reset internal static cache.
   */
  public function reset() {
    static::$files = array();
  }

}
