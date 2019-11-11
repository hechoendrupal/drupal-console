<?php

namespace Drupal\Console\Utils;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Console\Core\Utils\ConfigurationManager;

class Site
{
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $cacheServicesFile;

    /**
     * @var DrupalStyle
     */
    protected $io;

    /**
     * Site constructor.
     *
     * @param string               $appRoot
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        $appRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->appRoot = $appRoot;
        $this->configurationManager = $configurationManager;

        $output = new ConsoleOutput();
        $input = new ArrayInput([]);
        $this->io = new DrupalStyle($input, $output);
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
     * @return array
     */
    public function getStandardLanguages()
    {
        $standardLanguages = LanguageManager::getStandardLanguageList();
        $languages = [];
        foreach ($standardLanguages as $langcode => $standardLanguage) {
            $languages[$langcode] = $standardLanguage[0];
        }

        return $languages;
    }

    /**
     * @return array
     */
    public function getDatabaseTypes()
    {
        $this->loadLegacyFile('/core/includes/install.inc');
        $this->setMinimalContainerPreKernel();

        $driverDirectories = [
            $this->appRoot . '/core/lib/Drupal/Core/Database/Driver',
            $this->appRoot . '/drivers/lib/Drupal/Driver/Database'
        ];

        $driverDirectories = array_filter(
            $driverDirectories,
            function ($directory) {
                return is_dir($directory);
            }
        );

        $finder = new Finder();
        $finder->directories()
            ->in($driverDirectories)
            ->depth('== 0');

        $databases = [];
        foreach ($finder as $driver_folder) {
            if (file_exists($driver_folder->getRealpath() . '/Install/Tasks.php')) {
                $driver  = $driver_folder->getBasename();
                $installer = db_installer_object($driver);
                // Verify if database is installable
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

    protected function setMinimalContainerPreKernel()
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
            ->addMethodCall('setContainer', [new Reference('service_container')]);
        $container
            ->register('file_system', 'Drupal\Core\File\FileSystem')
            ->addArgument(new Reference('stream_wrapper_manager'))
            ->addArgument(Settings::getInstance())
            ->addArgument((new LoggerChannelFactory())->get('file'));

        \Drupal::setContainer($container);
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
     * @return mixed
     */
    public function getAutoload()
    {
        $autoLoadFile = $this->appRoot.'/autoload.php';

        return include $autoLoadFile;
    }

    /**
    * @param InputInterface $input
    * @return string
    */
    public function getMultisiteName($input)
    {
        $uri = $input->getParameterOption(['--uri', '-l'], 'default');

        if ($uri && !preg_match('/^(http|https):\/\//', $uri)) {
            $uri = sprintf('http://%s', $uri);
        }

        return  parse_url($uri, PHP_URL_HOST);
    }

    /**
     * @return boolean
     */
    public function multisiteMode($uri)
    {
        if ($uri != 'default') {
            return true;
        }

        return false;
    }

    /**
     * @param string $uri
     *
     * @return boolean
     */
    public function validMultisite($uri)
    {
        $sites = $this->getAllMultisites();

        if (isset($sites[$uri]) && is_dir($this->appRoot . "/sites/" . $sites[$uri])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    public function getMultisiteDir($uri)
    {
        if(!$this->validMultisite($uri)) {
            $this->io->error('Invalid multisite, please debug multisite using command drupal debug:mulltisite and choose one');
            exit();
        }

        return $this->getAllMultisites()[$uri];

    }

    /**
     * @return mixed
     */
    private function getAllMultisites()
    {
        $multiSiteFile = sprintf(
            '%s/sites/sites.php',
            $this->appRoot
        );

        if (file_exists($multiSiteFile)) {
            include $multiSiteFile;

            return $sites;
        } else {
            return null;
        }
    }

    public function getCachedServicesFile()
    {
        if (!$this->cacheServicesFile) {
            $configFactory = \Drupal::configFactory();
            $siteId = $configFactory->get('system.site')
                ->get('uuid');

            $directory = \Drupal::service('stream_wrapper.temporary')
                ->getDirectoryPath();

            $this->cacheServicesFile = $directory . '/' . $siteId .
                '-console.services.yml';
        }

        return $this->cacheServicesFile;
    }

    public function cachedServicesFileExists()
    {
        return file_exists($this->getCachedServicesFile());
    }

    public function removeCachedServicesFile()
    {
        if ($this->cachedServicesFileExists()) {
            unlink($this->getCachedServicesFile());
        }
    }

    /**
     * @param string $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }
}
