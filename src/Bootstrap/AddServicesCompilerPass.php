<?php

namespace Drupal\Console\Bootstrap;

use Drupal\Console\Utils\Site;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
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

        $servicesFiles = [
            $this->root. DRUPAL_CONSOLE_CORE . 'services.yml',
            $this->root. DRUPAL_CONSOLE . 'uninstall.services.yml',
            $this->root. DRUPAL_CONSOLE . 'services.yml'
        ];

        foreach ($servicesFiles as $servicesFile) {
            if (file_exists($servicesFile)) {
                $loader->load($servicesFile);
            }
        }

        $container->get('console.configuration_manager')
            ->loadConfiguration($this->root)
            ->getConfiguration();

        /**
         * @var Site $site
         */
        $site = $container->get('console.site');
        \Drupal::getContainer()->set(
            'console.root',
            $this->root
        );

        if (!$this->rebuild && $site->cachedServicesFileExists()) {
            $loader->load($site->getCachedServicesFile());
        } else {
            $site->removeCachedServicesFile();
            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in(
                    sprintf(
                        '%s/config/services',
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

            if ($servicesData) {
                file_put_contents(
                    $site->getCachedServicesFile(),
                    Yaml::dump($servicesData, 4, 2)
                );
            }
        }

        $extendServicesFiles = [
            $this->root . DRUPAL_CONSOLE . 'extend.console.services.yml',
            $this->root . DRUPAL_CONSOLE . 'extend.console.uninstall.services.yml',
        ];

        foreach ($extendServicesFiles as $extendServicesFile) {
            if (file_exists($extendServicesFile)) {
                $loader->load($extendServicesFile);
            }
        }

        $configurationManager = $container->get('console.configuration_manager');
        $directory = $configurationManager->getConsoleDirectory() . 'extend/';
        $autoloadFile = $directory . 'vendor/autoload.php';
        if (is_file($autoloadFile)) {
            include_once $autoloadFile;

            $extendServicesFiles = [
                $directory . 'extend.console.services.yml',
                $directory . 'extend.console.uninstall.services.yml',
            ];

            foreach ($extendServicesFiles as $extendServicesFile) {
                if (file_exists($extendServicesFile)) {
                    $loader->load($extendServicesFile);
                }
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
