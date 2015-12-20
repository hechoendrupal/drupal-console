<?php

namespace Drupal\Console\Command;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;

abstract class ContainerAwareCommand extends Command
{
    /**
     * @var array
     */
    private $services;

    /**
     * @var array
     */
    private $events;

    /**
     * Gets the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     *   A ContainerInterface instance.
     */
    protected function getContainer()
    {
        if (!$this->getKernelHelper()) {
            return null;
        }

        if (!$this->getKernelHelper()->getKernel()) {
            return null;
        }

        return $this->getKernelHelper()->getKernel()->getContainer();
    }

    /**
     * @param bool $tag
     *
     * @return array list of modules
     */
    public function getMigrations($tag = false)
    {
        $entity_manager = $this->getEntityManager();
        $migration_storage = $entity_manager->getStorage('migration');

        $entity_query_service = $this->getEntityQuery();
        $query = $entity_query_service->get('migration');

        if ($tag) {
            $query->condition('migration_tags.*', $tag);
        }

        $results = $query->execute();

        $migration_entities = $migration_storage->loadMultiple($results);

        $migrations = array();
        foreach ($migration_entities as $migration) {
            $migrations[$migration->id()]['tags'] = implode(', ', $migration->migration_tags);
            $migrations[$migration->id()]['description'] = ucwords($migration->label());
        }

        return $migrations;
    }

    public function getRestDrupalConfig()
    {
        $configFactory = $this->getConfigFactory();
        if (!$configFactory) {
            return null;
        }

        return $configFactory->get('rest.settings')->get('resources') ?: [];
    }

    /**
     * [geRest get a list of Rest Resouces].
     *
     * @param bool $rest_status return Rest Resources by status
     *
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
        $sort_resources = function ($resource_a, $resource_b) {
            return strcmp($resource_a['label'], $resource_b['label']);
        };
        if (!empty($available_resources['enabled'])) {
            uasort($available_resources['enabled'], $sort_resources);
        }
        if (!empty($available_resources['disabled'])) {
            uasort($available_resources['disabled'], $sort_resources);
        }

        if (isset($available_resources[$rest_status])) {
            return array($rest_status => $available_resources[$rest_status]);
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

    public function getEvents()
    {
        if (null === $this->events) {
            $this->events = [];
            $this->events = array_keys($this->getEventDispatcher()->getListeners());
        }

        return $this->events;
    }

    public function getRouteProvider()
    {
        return $this->hasGetService('router.route_provider');
    }

    public function getRouterBuilder()
    {
        return $this->hasGetService('router.builder');
    }

    /**
     * @param $rest
     * @param $rest_resources_ids
     * @param $translator
     *
     * @return mixed
     */
    public function validateRestResource($rest, $rest_resources_ids, $translator)
    {
        if (in_array($rest, $rest_resources_ids)) {
            return $rest;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    $translator->trans('commands.rest.disable.messages.invalid-rest-id'),
                    $rest
                )
            );
        }
    }

    /**
     * @return \Drupal\Core\Config\ConfigFactoryInterface
     */
    public function getConfigFactory()
    {
        return $this->hasGetService('config.factory');
    }

    /**
     * @return \Drupal\Core\State\StateInterface
     */
    public function getState()
    {
        return $this->hasGetService('state');
    }

    public function getConfigStorage()
    {
        return $this->hasGetService('config.storage');
    }

    /**
     * @return \Drupal\Core\Database\Connection
     */
    public function getDatabase()
    {
        return $this->hasGetService('database');
    }

    /**
     * @return \Drupal\Core\Datetime\DateFormatter;
     */
    public function getDateFormatter()
    {
        return $this->hasGetService('date.formatter');
    }

    /**
     * @return \Drupal\Core\Config\ConfigManagerInterface
     */
    public function getConfigManager()
    {
        return $this->hasGetService('config.manager');
    }

    /**
     * @return \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->hasGetService('event_dispatcher');
    }

    public function getEntityManager()
    {
        return $this->hasGetService('entity.manager');
    }

    public function getCron()
    {
        return $this->hasGetService('cron');
    }

    /**
     * @return \Drupal\Core\ProxyClass\Lock\DatabaseLockBackend
     */
    public function getDatabaseLockBackend()
    {
        return $this->hasGetService('lock');
    }

    public function getViewDisplayManager()
    {
        return $this->hasGetService('plugin.manager.views.display');
    }

    public function getWebprofilerForms()
    {
        $profiler = $this->hasGetService('profiler');
        $tokens = $profiler->find(null, null, 1000, null, '', '');

        $forms = array();
        foreach ($tokens as $token) {
            $token = [$token['token']];
            $profile = $profiler->loadProfile($token);
            $formCollector = $profile->getCollector('forms');
            $collectedForms = $formCollector->getForms();
            if (empty($forms)) {
                $forms = $collectedForms;
            } elseif (!empty($collectedForms)) {
                $forms = array_merge($forms, $collectedForms);
            }
        }
        return $forms;
    }

    public function getEntityQuery()
    {
        return $this->hasGetService('entity.query');
    }

    public function getModuleInstaller()
    {
        return $this->hasGetService('module_installer');
    }

    public function getModuleHandler()
    {
        return $this->hasGetService('module_handler');
    }

    public function getPluginManagerRest()
    {
        return $this->hasGetService('plugin.manager.rest');
    }

    public function getContextRepository()
    {
        return $this->hasGetService('context.repository');
    }

    /**
     * getTestDiscovery return a service object for Simpletest.
     *
     * @return Drupal\simpletest\TestDiscovery
     */
    public function getTestDiscovery()
    {
        return $this->hasGetService('test_discovery');
    }

    public function getHttpClient()
    {
        return $this->hasGetService('http_client');
    }

    public function getSerializerFormats()
    {
        $container = $this->getContainer();
        if (!$container) {
            return null;
        }
        return $container->getParameter('serializer.formats');
    }

    public function getStringTanslation()
    {
        return $this->hasGetService('string_translation');
    }


    public function getAuthenticationProviders()
    {
        return $this->hasGetService('authentication_collector')->getSortedProviders();
    }

    /**
     * @return \Drupal\system\SystemManager
     */
    public function getSystemManager()
    {
        return $this->hasGetService('system.manager');
    }

    /**
     * @return array
     */
    public function getConnectionInfo()
    {
        return  Database::getConnectionInfo();
    }

    /**
     * @return \Drupal\Core\Site\Settings
     */
    public function getSettings()
    {
        if ($settings = $this->hasGetService('settings')) {
            return $settings;
        }

        $kernelHelper = $this->getKernelHelper();
        $drupal = $this->getDrupalHelper();
        if ($kernelHelper && $drupal) {
            $settings = Settings::initialize(
                $drupal->getRoot(),
                $kernelHelper->getSitePath(),
                $kernelHelper->getClassLoader()
            );

            return $settings;
        }

        return null;
    }

    /**
     * @return \Drupal\Core\Extension\ThemeHandlerInterface
     */
    public function getThemeHandler()
    {
        return $this->hasGetService('theme_handler');
    }

    /**
     * @return \Drupal\Core\Extension\ThemeHandlerInterface
     */
    public function getPassHandler()
    {
        return $this->hasGetService('password');
    }

    public function hasGetService($serviceId)
    {
        if (!$this->getContainer()) {
            return null;
        }

        if ($this->getContainer()->has($serviceId)) {
            return $this->getContainer()->get($serviceId);
        }

        return null;
    }

    public function validateEventExist($event_name, $events = null)
    {
        if (!$events) {
            $events = $this->getEvents();
        }

        return $this->getValidator()->validateEventExist($event_name, $events);
    }

    public function validateModuleExist($module_name)
    {
        return $this->getValidator()->validateModuleExist($module_name);
    }

    public function validateServiceExist($service_name, $services = null)
    {
        if (!$services) {
            $services = $this->getServices();
        }

        return $this->getValidator()->validateServiceExist($service_name, $services);
    }

    public function validateModule($machine_name)
    {
        $machine_name = $this->validateMachineName($machine_name);
        $modules = $this->getSite()->getModules(false, false, true, true, true);
        if (in_array($machine_name, $modules)) {
            throw new \InvalidArgumentException(sprintf('Module "%s" already exist.', $machine_name));
        }

        return $machine_name;
    }

    public function validateModuleName($module_name)
    {
        return $this->getValidator()->validateModuleName($module_name);
    }

    public function validateModulePath($module_path, $create_dir = false)
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

    public function generateEntity($entity_definition, $entity_type)
    {
        $entity_manager = $this->getEntityManager();
        $entity_storage = $entity_manager->getStorage($entity_type);
        $entity = $entity_storage->createFromStorageRecord($entity_definition);

        return $entity;
    }

    public function updateEntity($entity_id, $entity_type, $entity_definition)
    {
        $entity_manager = $this->getEntityManager();
        $entity_storage = $entity_manager->getStorage($entity_type);
        $entity = $entity_storage->load($entity_id);
        $entity_updated = $entity_storage->updateFromStorageRecord($entity, $entity_definition);

        return $entity_updated;
    }
}
