<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Utils\TranslatorManager;
use Drupal\Core\Cache\ListCacheBinsPass;

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
     * @var YamlFileLoader
     */
    protected $loader;

    /**
     * AddCommandsCompilerPass constructor.
     *
     * @param string $root
     */
    public function __construct($root)
    {
        $this->root = $root;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $this->loader = new YamlFileLoader(
            $container,
            new FileLocator($this->root)
        );

        // Load DrupalConsole services
        $this->addDrupalConsoleServices();

        // Load configuration from directory
        $container->get('console.configuration_manager')
            ->loadConfiguration($this->root)
            ->getConfiguration();

        // Set console.root services
        $container->set(
            'console.root',
            $this->root
        );

        // Load DrupalConsole services
        $this->addDrupalConsoleConfigServices();

        // Load DrupalConsole extended services
        $this->addDrupalConsoleExtendedServices();

        // The AddServicesCompilerPass cache pass is executed before the
        // ListCacheBinsPass causing exception: ParameterNotFoundException: You
        // have requested a non-existent parameter "cache_default_bin_backends"
        $cache_pass = new ListCacheBinsPass();
        $cache_pass->process($container);

        // Override TranslatorManager service definition
        $translatorManagerDefinition = $container
            ->getDefinition('console.translator_manager');
        $translatorManagerDefinition->setClass(TranslatorManager::class);

        // Set console.service_definitions service
        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );

        // Set console.invalid_commands service
        $container->set(
            'console.invalid_commands',
            null
        );
    }

    protected function addDrupalConsoleServiceFiles($servicesFiles)
    {
        foreach ($servicesFiles as $servicesFile) {
            if (file_exists($servicesFile)) {
                $this->loader->load($servicesFile);
            }
        }
    }

    protected function addDrupalConsoleServices()
    {
        $servicesFiles = [
            $this->root. DRUPAL_CONSOLE_CORE . 'services.yml',
            $this->root. DRUPAL_CONSOLE . 'uninstall.services.yml',
            $this->root. DRUPAL_CONSOLE . 'services.yml'
        ];

        $this->addDrupalConsoleServiceFiles($servicesFiles);
    }

    protected function addDrupalConsoleConfigServices()
    {
        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(
                sprintf(
                    '%s/config/services',
                    $this->root.DRUPAL_CONSOLE
                )
            );

        foreach ($finder as $file) {
            $this->loader->load($file->getPathName());
        }
    }

    protected function addDrupalConsoleExtendedServices()
    {
        $servicesFiles = [
            $this->root . DRUPAL_CONSOLE . 'extend.console.services.yml',
            $this->root . DRUPAL_CONSOLE . 'extend.console.uninstall.services.yml',
        ];

        $this->addDrupalConsoleServiceFiles($servicesFiles);
    }
}
