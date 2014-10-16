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

  private $route_provider;

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
        } elseif ($core) {
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

  public function getRouteProvider()
  {
    if (null === $this->route_provider) {
      $this->route_provider = $this->getContainer()->get('router.route_provider');
    }

    return $this->route_provider;
  }

  /**
   * @return \Drupal\AppConsole\Utils\Validators
   */
  public function getValidator()
  {
    return $this->getContainer()->get('console.validators');
  }

  public function getConfigFactory(){
    return $this->getContainer()->get('config.factory');
  }

  public function getConfigStorage(){
    return $this->getContainer()->get('config.storage');
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

  public function validateModule($machine_name)
  {
    $machine_name = $this->validateMachineName($machine_name);
    $modules = array_merge($this->getModules(true), $this->getModules());
    if (in_array($machine_name, $modules)) {
      throw new \InvalidArgumentException(sprintf('Module "%s" already exist.', $machine_name));
    }
    return $machine_name;
  }

  public function validateModuleName($module_name)
  {
    return $this->getValidator()->validateModuleName($module_name);
  }

  public function validateModulePath($module_path, $create_dir=false)
  {
    return $this->getValidator()->validateModulePath($module_path, $create_dir);
  }

  public function validateClassName($class_name)
  {
    return $this->getValidator()->validateClassName($class_name);
  }

  public function validateMachineName($machine_name)
  {
    return $this->getValidator()->validateMachineName($machine_name);
  }

  /**
   * @return \Drupal\AppConsole\Utils\StringUtils
   */
  public function getStringUtils()
  {
    return $this->getContainer()->get('console.string_utils');
  }
}
