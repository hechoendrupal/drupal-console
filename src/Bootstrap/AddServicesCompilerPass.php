<?php

namespace Drupal\Console\Bootstrap;

use Drupal\Console\Extension\Manager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;

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
     * AddCommandsCompilerPass constructor.
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
        $loader = new YamlFileLoader(
            $container,
            new FileLocator($this->root)
        );

        $loader->load($this->root.  DRUPAL_CONSOLE_CORE . 'services.yml');
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
        $modules = $extensionManager->discoverModules()
            ->showCore()
            ->showNoCore()
            ->showInstalled()
            ->getList(true);

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(
                sprintf(
                    '%s/config/services/drupal-core',
                    $this->root.DRUPAL_CONSOLE
                )
            );

        foreach ($finder as $file) {
            if (in_array($file->getBasename('.yml'), $modules)) {
                $loader->load($file->getPathName());
            }
        }

        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );
    }
}
