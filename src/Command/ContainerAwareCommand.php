<?php

namespace Drupal\AppConsole\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\AppConsole\Command\Command;
use Drupal\Core\Extension\ExtensionDiscovery;
//use Drupal\Core\Entity\EntityStorageInterface;

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
      $extensionDiscover = new ExtensionDiscovery(\Drupal::root());
      $moduleList = $extensionDiscover->scan('module');
      foreach ($moduleList as $name => $filename) {
        if ($core) {
          array_push($this->modules, $name);
        } elseif (!preg_match('/^core/', $filename->getPathname())) {
          array_push($this->modules, $name);
        }
      }
    }

    return $this->modules;
  }

  /**
   * [getModules description]
   * @param  boolean $core Return core modules
   * @return array list of modules
   */
  public function getMigrations($version = false)
  {

    $entity_manager = $this->getEntityManager();
    $migration_storage = $entity_manager->getStorage('migration');

    $entity_query_service = $this->getEntityQuery();
    $query = $entity_query_service->get('migration');

    if($version and in_array($version, array(6,7))) {
        $query->condition('migration_groups.*', 'Drupal ' . $version);
    }

    $results = $query->execute();


    $migration_entities = $migration_storage->loadMultiple($results);

    $migrations = array();
    foreach ($migration_entities as $migration) {
      $migrations[$migration->id]['version'] = ucfirst($migration->migration_groups[0]);
      $label = str_replace($migrations[$migration->id]['version'], '', $migration->label);
      $migrations[$migration->id]['description'] = ucwords($label);
    }

    return $migrations;
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

  public function getConfigFactory(){
    return $this->getContainer()->get('config.factory');
  }

  public function getConfigStorage(){
    return $this->getContainer()->get('config.storage');
  }

  public function getEntityManager(){
    return $this->getContainer()->get('entity.manager');
  }

  public function getEntityQuery(){
    return $this->getContainer()->get('entity.query');
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
    $machine_name = $this->getValidator()->validateMachineName($machine_name);

    if ($this->getEntityManager()->hasDefinition($machine_name)) {
      throw new \InvalidArgumentException(sprintf('Machine name "%s" is duplicated.', $machine_name));
    }

    return $machine_name;
  }

  public function validateSpaces($name)
  {
    return $this->getValidator()->validateSpaces($name);
  }

  public function removeSpaces($name)
  {
    return $this->getValidator()->removeSpaces($name);
  }

}
