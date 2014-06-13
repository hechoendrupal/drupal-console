<?php

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

abstract class ContainerAwareCommand extends Command implements ContainerAwareInterface
{

  private $container;

  private $modules;

  private $services;

  /**
   * @return ContainerInterface
   */
  protected function getContainer()
  {
    if (null === $this->container) {
      $this->container = $this->getApplication()->getKernel()->getContainer();
    }

    return $this->container;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * [getModules description]
   * @param  boolean $core Return core modules
   * @return array list of modules
   */
  public function getModules($core = false)
  {
    if (null === $this->modules) {
      $this->modules = [];
      //get all modules
      $all_modules = \system_rebuild_module_data();

      // Filter modules
      foreach ($all_modules as $name => $filename) {
        if (!preg_match('/^core/', $filename->uri) && !$core) {
          array_push($this->modules, $name);
        } else if ($core) {
          array_push($this->modules, $name);
        }
      }
    }
    return $this->modules;
  }

  public function getServices()
  {
    if (null === $this->services) {
      $this->services = [];
      $this->services = $this->getContainer()->getServiceIds();
    }

    return $this->services;
  }

  private function getValidator()
  {
    return $this->getContainer()->get('console.validators');
  }

  public function validateModuleExist($module_name)
  {
    return $this->getValidator()->validateModuleExist($module_name, $this->getModules());
  }

  public function validateServiceExist($service_name, $services = null)
  {
    if (!$services)
      $services = $this->getServices();
    return $this->getValidator()->validateServiceExist($service_name, $services);
  }

}
