<?php

namespace Drupal\Console\Utils;

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
    protected $consoleRoot;
    /**
     * @var string
     */
    protected $siteRoot;

    /**
     * AddCommandsCompilerPass constructor.
     * @param string $consoleRoot
     * @param string $siteRoot
     */
    public function __construct($consoleRoot, $siteRoot) {
        $this->consoleRoot = $consoleRoot;
        $this->siteRoot = $siteRoot;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator($this->consoleRoot)
        );
        $loader->load($this->siteRoot.'vendor/drupal/console-core/services.yml');
        $loader->load('services.yml');

        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(sprintf('%s/config/services/', $this->consoleRoot));
        foreach ($finder as $file) {
            $loader->load($file->getPathName());
        }
    }
}
