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
class AddCommandsCompilerPass implements CompilerPassInterface
{

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * AddCommandsCompilerPass constructor.
     * @param string $consoleRoot
     */
    public function __construct($consoleRoot) {
        $this->consoleRoot = $consoleRoot;
    }

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator($this->consoleRoot));
        $loader->load('services.yml');

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(sprintf('%s/config/services/', $this->consoleRoot));
        foreach ($finder as $file) {
            $loader->load($file->getPathName());
        }
    }
}
