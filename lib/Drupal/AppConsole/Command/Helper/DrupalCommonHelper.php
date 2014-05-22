<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\Helper\DrupalBootstrapHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperInterface;

class DrupalCommonHelper extends Helper implements HelperInterface{

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName() {
    return 'drupal_common';
  }

  public function getDrupalGetPath($type, $name){
    return \drupal_get_path($type, $name);
  }

}
