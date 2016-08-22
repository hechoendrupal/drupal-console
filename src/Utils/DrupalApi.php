<?php

/**
 * @file
 * Contains Drupal\Console\Utils\Site.
 */

namespace Drupal\Console\Utils;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\Cache;

/**
 * Class DrupalHelper
 * @package Drupal\Console\Utils
 */
class DrupalApi
{
    protected $appRoot;
    protected $entityTypeManager;

    private $caches = [];
    private $bundles = [];
    private $vocabularies = [];
    private $roles = [];

    /**
     * ServerCommand constructor.
     * @param $appRoot
     */
    public function __construct($appRoot, $entityTypeManager)
    {
        $this->appRoot = $appRoot;
        $this->entityTypeManager = $entityTypeManager;
    }

    public function loadLegacyFile($legacyFile, $relative = true)
    {
        if ($relative) {
            $legacyFile = realpath(
                sprintf('%s/%s', $this->appRoot, $legacyFile)
            );
        }

        if (file_exists($legacyFile)) {
            include_once $legacyFile;

            return true;
        }

        return false;
    }

    /**
     * @return mixed array
     */
    public function getStandardLanguages()
    {
        $standard_languages = LanguageManager::getStandardLanguageList();
        $languages = [];
        foreach ($standard_languages as $langcode => $standard_language) {
            $languages[$langcode] = $standard_language[0];
        }

        return $languages;
    }

    public function setMinimalContainerPreKernel()
    {
        // Create a minimal mocked container to support calls to t() in the pre-kernel
        // base system verification code paths below. The strings are not actually
        // used or output for these calls.
        $container = new ContainerBuilder();
        $container->setParameter('language.default_values', Language::$defaultValues);
        $container
            ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
            ->addArgument('%language.default_values%');
        $container
            ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
            ->addArgument(new Reference('language.default'));

        // Register the stream wrapper manager.
        $container
            ->register('stream_wrapper_manager', 'Drupal\Core\StreamWrapper\StreamWrapperManager')
            ->addMethodCall('setContainer', array(new Reference('service_container')));
        $container
            ->register('file_system', 'Drupal\Core\File\FileSystem')
            ->addArgument(new Reference('stream_wrapper_manager'))
            ->addArgument(Settings::getInstance())
            ->addArgument((new LoggerChannelFactory())->get('file'));

        \Drupal::setContainer($container);
    }
    /**
     * @return mixed array
     */
    public function getDatabaseTypes()
    {
        $this->loadLegacyFile('/core/includes/install.inc');
        $this->setMinimalContainerPreKernel();

        $finder = new Finder();
        $finder->directories()
            ->in($this->appRoot . '/core/lib/Drupal/Core/Database/Driver')
            ->depth('== 0');

        $databases = [];
        foreach ($finder as $driver_folder) {
            if (file_exists($driver_folder->getRealpath() . '/Install/Tasks.php')) {
                $driver  = $driver_folder->getBasename();
                $installer = db_installer_object($driver);
                // Verify is database is installable
                if ($installer->installable()) {
                    $reflection = new \ReflectionClass($installer);
                    $install_namespace = $reflection->getNamespaceName();
                    // Cut the trailing \Install from namespace.
                    $driver_class = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
                    $databases[$driver] = ['namespace' => $driver_class, 'name' =>$installer->name()];
                }
            }
        }

        return $databases;
    }

    public function getDatabaseTypeDriver($driver)
    {
        // We cannot use Database::getConnection->getDriverClass() here, because
        // the connection object is not yet functional.
        $task_class = "Drupal\\Core\\Database\\Driver\\{$driver}\\Install\\Tasks";
        if (class_exists($task_class)) {
            return new $task_class();
        } else {
            $task_class = "Drupal\\Driver\\Database\\{$driver}\\Install\\Tasks";
            return new $task_class();
        }
    }

    /**
     * @return mixed array
     */
    public function getProfiles()
    {
        $parser = new Parser();
        $finder = new Finder();
        $finder->files()
            ->name('*.info.yml')
            ->in($this->appRoot . '/core/profiles/')
            ->in($this->appRoot . '/profiles/')
            ->contains('type: profile')
            ->notContains('hidden: true')
            ->depth('1');

        $profiles = [];
        foreach ($finder as $file) {
            $profile_key = $file->getBasename('.info.yml');
            $profiles[$profile_key] = $parser->parse($file->getContents());
        }

        return $profiles;
    }

    /**
     * @return string
     */
    public function getDrupalVersion()
    {
        return \Drupal::VERSION;
    }

    /**
     * Auxiliary function to get all available drupal caches.
     *
     * @return array The all available drupal caches
     */
    public function getCaches()
    {
        if (!$this->caches) {
            foreach (Cache::getBins() as $name => $bin) {
                $this->caches[$name] = $bin;
            }
        }

        return $this->caches;
    }

    /**
     * Validate if a string is a valid cache.
     *
     * @param string $cache The cache name
     *
     * @return mixed The cache name if valid or FALSE if not valid
     */
    public function isValidCache($cache)
    {
        // Get the valid caches
        $caches = $this->getCaches();
        $cacheKeys = array_keys($caches);
        $cacheKeys[] = 'all';

        if (!in_array($cache, array_values($cacheKeys))) {
            return false;
        }

        return $cache;
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        if (!$this->bundles) {
            $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

            foreach ($nodeTypes as $nodeType) {
                $this->bundles[$nodeType->id()] = $nodeType->label();
            }
        }

        return $this->bundles;
    }

    /**
     * @return array
     */
    public function getVocabularies()
    {
        if (!$this->vocabularies) {
            $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();

            foreach ($vocabularies as $vocabulary) {
                $this->vocabularies[$vocabulary->id()] = $vocabulary->label();
            }
        }

        return $this->vocabularies;
    }

    /**
     * @param bool|FALSE $reset
     * @param bool|FALSE $authenticated
     * @param bool|FALSE $anonymous
     *
     * @return array
     */
    public function getRoles($reset=false, $authenticated=true, $anonymous=false)
    {
        if ($reset || !$this->roles) {
            $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
            if (!$authenticated) {
                unset($roles['authenticated']);
            }
            if (!$anonymous) {
                unset($roles['anonymous']);
            }
            foreach ($roles as $role) {
                $this->roles[$role->id()] = $role->label();
            }
        }

        return $this->roles;
    }

    /* @todo fix */
    /**
     * Validate if module name exist.
     *
     * @param string $moduleName Module name
     *
     * @return string
     */
    public function validateModuleExist($moduleName)
    {
        if (!$this->isModule($moduleName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Module "%s" is not in your application. Try generate:module to create it.',
                    $moduleName
                )
            );
        }

        return $moduleName;
    }

    /**
     * Check if module name exist.
     *
     * @param string $moduleName Module name
     *
     * @return string
     */
    public function isModule($moduleName)
    {
        $modules = $this->getSite()->getModules(false, true, true, true, true, true);

        return in_array($moduleName, $modules);
    }

    /**
     * @param $moduleList
     * @return array
     */
    public function getMissingModules($moduleList)
    {
        $modules = $this->getSite()->getModules(true, true, true, true, true, true);

        return array_diff($moduleList, $modules);
    }

    /**
     * Validate if module is installed.
     *
     * @param string $moduleName Module name
     *
     * @return string
     */
    public function validateModuleInstalled($moduleName)
    {
        if (!$this->isModuleInstalled($moduleName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Module "%s" is not installed. Try module:install to install it.',
                    $moduleName
                )
            );
        }

        return $moduleName;
    }

    /**
     * Check if module is installed.
     *
     * @param  $moduleName
     * @return bool
     */
    public function isModuleInstalled($moduleName)
    {
        $modules = $this->getSite()->getModules(false, true, false, true, true, true);

        return in_array($moduleName, $modules);
    }

    /**
     * @param $moduleList
     * @return array
     */
    public function getUninstalledModules($moduleList)
    {
        $modules = $this->getSite()->getModules(true, true, false, true, true, true);

        return array_diff($moduleList, $modules);
    }
}
