<?php

/**
 * @file
 * Contains Drupal\Console\Utils\Site.
 */

namespace Drupal\Console\Utils;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * Class DrupalHelper
 * @package Drupal\Console\Utils
 */
class Site
{
    protected $appRoot;

    /**
     * ServerCommand constructor.
     * @param $appRoot
     */
    public function __construct($appRoot) {
        $this->appRoot = $appRoot;
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
}
