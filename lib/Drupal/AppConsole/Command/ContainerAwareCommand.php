<?php

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

abstract class ContainerAwareCommand extends Command implements ContainerAwareInterface {

  private $container;

  /**
   * @return ContainerInterface
   */
  protected function getContainer() {
    if (null === $this->container) {
      $this->container = $this->getApplication()->getKernel()->getContainer();
    }

    return $this->container;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = null) {
    $this->container = $container;
  }

  /**
   * [getModules description]
   * @param  boolean $core Return core modules
   * @return array list of modules
   */
  public function getModules($core = false) {
    // modules collection
    $modules = array();
    //get all modules
    $all_modules = \system_rebuild_module_data();

    // Filter modules
    foreach ($all_modules as $name => $filename) {
      if ( !preg_match('/^core/',$filename->uri) && !$core){
        array_push($modules, $name);
      }
      else if ($core){
        array_push($modules, $name);
      }
    }
    return $modules;
  }

  public function getServices() {
    return $this->getContainer()->getServiceIds();
  }

  public function getValidator(){
    return $this->getContainer()->get('console.validators');
  }
}
