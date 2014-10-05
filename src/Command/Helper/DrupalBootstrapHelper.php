<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class DrupalBootstrapHelper extends Helper
{
  private $booting = false;
  /**
   * @param string $pathToBootstrapFile
   */
  public function bootstrapConfiguration($pathToBootstrapFile)
  {
    if ($pathToBootstrapFile) {
      require_once $pathToBootstrapFile;
      \drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
      $this->booting = true;
    }
    else {
      return false;
    }
  }

  public function bootstrapCode()
  {
    if ($this->booting) {
      \drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);
    }
  }

  public function getDrupalRoot()
  {
    return $this->booting ? DRUPAL_ROOT : getcwd();
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName()
  {
    return 'bootstrap';
  }
}
