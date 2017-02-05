<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Utils\ExtendExtensionManager;
use Drupal\Console\Utils\TranslatorManager;
use Drupal\Console\Extension\Extension;
use Drupal\Console\Extension\Manager;

/**
 * FindCommandsCompilerPass
 */
class AddServicesCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var boolean
     */
    protected $rebuild;

    /**
     * AddCommandsCompilerPass constructor.
     *
     * @param string  $root
     * @param string  $appRoot
     * @param boolean $rebuild
     */
    public function __construct($root, $appRoot, $rebuild = false)
    {
        $this->root = $root;
        $this->appRoot = $appRoot;
        $this->rebuild = $rebuild;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator($this->root)
        );

        $loader->load($this->root. DRUPAL_CONSOLE_CORE . 'services.yml');
        $loader->load($this->root. DRUPAL_CONSOLE . 'services-drupal-install.yml');
        $loader->load($this->root. DRUPAL_CONSOLE . 'services.yml');

        $basePath = $container->get('console.site')->getCacheDirectory();
        $consoleServicesFile = $basePath.'/console.services.yml';
        $consoleExtendServicesFile = $basePath.'/extend.console.services.yml';
        $consoleExtendConfigFile = $basePath.'/extend.console.config.yml';

        if ($basePath && !$this->rebuild && file_exists($consoleServicesFile)) {
            $loader->load($consoleServicesFile);
            if (file_exists($consoleExtendServicesFile)) {
                $loader->load($consoleExtendServicesFile);
            }
        } else {
            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in(
                    sprintf(
                        '%s/config/services/drupal-console',
                        $this->root.DRUPAL_CONSOLE
                    )
                );

            $servicesData = [];
            foreach ($finder as $file) {
                $loader->load($file->getPathName());
                $servicesData = $this->extractServiceData(
                    $file->getPathName(),
                    $servicesData
                );
            }

            /**
             * @var Manager $extensionManager
             */
            $extensionManager = $container->get('console.extension_manager');
            /**
             * @var Extension[] $modules
             */
            $modules = $extensionManager->discoverModules()
                ->showCore()
                ->showNoCore()
                ->showInstalled()
                ->getList(false);

            foreach ($modules as $module) {
                if ($module->origin == 'core') {
                    $consoleServicesExtensionFile = $this->root . DRUPAL_CONSOLE .
                        'config/services/drupal-core/'.$module->getName().'.yml';
                    if (is_file($consoleServicesExtensionFile)) {
                        $loader->load($consoleServicesExtensionFile);
                        $servicesData = $this->extractServiceData(
                            $consoleServicesExtensionFile,
                            $servicesData
                        );
                    }
                }

                $consoleServicesExtensionFile = $this->appRoot . '/' .
                    $module->getPath() . '/console.services.yml';
                if (is_file($consoleServicesExtensionFile)) {
                    $loader->load($consoleServicesExtensionFile);
                    $servicesData = $this->extractServiceData(
                        $consoleServicesExtensionFile,
                        $servicesData
                    );
                }
            }

            /**
             * @var Extension[] $themes
             */
            $themes = $extensionManager->discoverThemes()
                ->showNoCore()
                ->showInstalled()
                ->getList(false);

            foreach ($themes as $theme) {
                $consoleServicesExtensionFile = $this->appRoot . '/' .
                    $theme->getPath() . '/console.services.yml';
                if (is_file($consoleServicesExtensionFile)) {
                    $loader->load($consoleServicesExtensionFile);
                    $servicesData = $this->extractServiceData(
                        $consoleServicesExtensionFile,
                        $servicesData
                    );
                }
            }

            if ($servicesData && is_writable($basePath)) {
                file_put_contents(
                    $consoleServicesFile,
                    Yaml::dump($servicesData, 4, 2)
                );
            }

            /**
             * @var ExtendExtensionManager $extendExtensionManager
             */
            $extendExtensionManager = $container->get('console.extend_extension_manager');
            $extendExtensionManager->processProjectPackages($this->root);
            $configData = $extendExtensionManager->getConfigData();
            if ($configData && is_writable($basePath)) {
                file_put_contents(
                    $consoleExtendConfigFile,
                    Yaml::dump($configData, 6, 2)
                );
            }
            $servicesData = $extendExtensionManager->getServicesData();
            if ($servicesData && is_writable($basePath)) {
                file_put_contents(
                    $consoleExtendServicesFile,
                    Yaml::dump($servicesData, 4, 2)
                );
            }

            $servicesFiles = $extendExtensionManager->getServicesFiles();
            foreach ($servicesFiles as $servicesFile) {
                $loader->load($servicesFile);
            }
        }

        $configurationManager = $container->get('console.configuration_manager');
        $directory = $configurationManager->getConsoleDirectory() . 'extend/';
        $autoloadFile = $directory . 'vendor/autoload.php';
        if (is_file($autoloadFile)) {
            include_once $autoloadFile;
            $extendServicesFile = $directory . 'extend.console.services.yml';
            if (is_file($extendServicesFile)) {
                $loader->load($extendServicesFile);
            }
        }

        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );

        $definition = $container->getDefinition('console.translator_manager');
        $definition->setClass(TranslatorManager::class);
    }

    /**
     * @param $filePath
     * @param $servicesData
     *
     * @return array
     */
    protected function extractServiceData($filePath, $servicesData)
    {
        $serviceFileData = Yaml::parse(
            file_get_contents($filePath)
        );

        $servicesData = array_merge_recursive(
            $servicesData,
            $serviceFileData
        );

        return $servicesData;
    }
}
