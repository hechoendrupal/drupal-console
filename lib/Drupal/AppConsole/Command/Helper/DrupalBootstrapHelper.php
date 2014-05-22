<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Drupal\Core\DrupalKernel;

class DrupalBootstrapHelper extends Helper {

  /**
   * @param string $pathToBootstrapFile
   */
  public function bootstrapConfiguration($pathToBootstrapFile) {
      require_once $pathToBootstrapFile;
      \drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
  }

  // ToDo: Evaluate delete this function, becuase was replaced by bootstrapCode
  public function bootstrapFull() {
    \drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  }

    public function bootstrapCode() {
    \drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);
  }

  public function getDrupalRoot(){
    return DRUPAL_ROOT;
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName() {
    return 'bootstrap';
  }
}
