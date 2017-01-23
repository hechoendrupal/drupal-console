<?php

namespace Drupal\Console\Bootstrap;

use Drupal\Console\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\TranslatorManager;

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
     * AddCommandsCompilerPass constructor.
     *
     * @param string $root
     * @param string $appRoot
     */
    public function __construct($root, $appRoot)
    {
        $this->root = $root;
        $this->appRoot = $appRoot;
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

        $loader->load($this->root.  DRUPAL_CONSOLE_CORE . 'services.yml');
        $loader->load($this->root.  DRUPAL_CONSOLE . 'services-drupal-install.yml');
        $loader->load($this->root.  DRUPAL_CONSOLE . 'services.yml');

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(
                sprintf(
                    '%s/config/services/drupal-console',
                    $this->root.DRUPAL_CONSOLE
                )
            );

        foreach ($finder as $file) {
            $loader->load($file->getPathName());
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
                $consoleServicesFile = $this->root . DRUPAL_CONSOLE .
                    'config/services/drupal-core/'.$module->getName().'.yml';
                if (is_file($consoleServicesFile)) {
                    $loader->load($consoleServicesFile);
                }
            }

            $consoleServicesFile = $this->appRoot . '/' .
                $module->getPath() . '/console.services.yml';
            if (is_file($consoleServicesFile)) {
                $loader->load($consoleServicesFile);
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
            $consoleServicesFile = $this->appRoot . '/' .
                $theme->getPath() . '/console.services.yml';
            if (is_file($consoleServicesFile)) {
                $loader->load($consoleServicesFile);
            }
        }

        $configurationManager = $container->get('console.configuration_manager');
        $directory = $configurationManager->getConsoleDirectory() . 'extend/';
        $autoloadFile = $directory . 'vendor/autoload.php';
        if (is_file($autoloadFile)) {
            include_once $autoloadFile;
            $extendService = $directory . 'extend.console.services.yml';
            if (is_file($extendService)) {
                $loader->load($extendService);
            }
        }

        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );

        $definition = $container->getDefinition('console.translator_manager');
        $definition->setClass(TranslatorManager::class);
    }
}
