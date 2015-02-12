<?php

namespace Drupal\AppConsole\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\AppConsole\Command\Command;
use Drupal\Core\Extension\ExtensionDiscovery;

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
  public function getMigrations($group = false)
  {

    $entity_manager = $this->getEntityManager();
    $migration_storage = $entity_manager->getStorage('migration');

    $entity_query_service = $this->getEntityQuery();
    $query = $entity_query_service->get('migration');

    if($group) {
        $query->condition('migration_groups.*', $group);
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

  public function getRestDrupalConfig(){
    return $this->getConfigFactory()
      ->get('rest.settings')->get('resources') ?: [];
  }

  /**
   * [geRest get a list of Rest Resouces]
   * @param  boolean $status return Rest Resources by status
   * @return array list of rest resources
   */
  public function getRestResources($rest_status = false)
  {

    $config = $this->getRestDrupalConfig();

    $resourcePluginManager = $this->getPluginManagerRest();
    $resources = $resourcePluginManager->getDefinitions();

    $enabled_resources = array_combine(array_keys($config), array_keys($config));
    $available_resources = array('enabled' => array(), 'disabled' => array());

    foreach ($resources as $id => $resource) {
      $status = in_array($id, $enabled_resources) ? 'enabled' : 'disabled';
      $available_resources[$status][$id] = $resource;
    }

    // Sort the list of resources by label.
    $sort_resources = function($resource_a, $resource_b) {
      return strcmp($resource_a['label'], $resource_b['label']);
    };
    if (!empty($available_resources['enabled'])) {
      uasort($available_resources['enabled'], $sort_resources);
    }
    if (!empty($available_resources['disabled'])) {
      uasort($available_resources['disabled'], $sort_resources);
    }

    if(isset($available_resources[$rest_status])) {
      return array( $rest_status => $available_resources[$rest_status]);
    }

    return $available_resources;
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
   * @param $rest
   * @param $rest_resources_ids
   * @param $translator
   * @return mixed
   */
  public function validateRestResource($rest, $rest_resources_ids, $translator)
  {
    if (in_array($rest, $rest_resources_ids)) {
      return $rest;
    } else {
      throw new \InvalidArgumentException(sprintf($translator->trans('commands.rest.disable.messages.invalid-rest-id'), $rest));
    }
  }

  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
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

  public function getModuleInstaller() {
    return $this->getContainer()->get('module_installer');
  }

  public function getPluginManagerRest(){
    return $this->getContainer()->get('plugin.manager.rest');
  }

  public function getHttpClient() {
    return $this->getContainer()->get('http_client');
  }

  public function getSerializerFormats() {
    return $this->getContainer()->getParameter('serializer.formats');
  }

  public function getAuthenticationProviders() {
    return $this->getContainer()->get('authentication')->getSortedProviders();
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
