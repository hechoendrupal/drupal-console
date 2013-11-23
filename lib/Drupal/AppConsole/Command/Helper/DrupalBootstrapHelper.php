<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\DrupalKernel;

class DrupalBootstrapHelper extends Helper {

  /**
   * @param string $pathToBootstrapFile
   */
  public function bootstrap($pathToBootstrapFile) {
      require_once $pathToBootstrapFile;
      \drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName() {
    return 'bootstrap';
  }
}
