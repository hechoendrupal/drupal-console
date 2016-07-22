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
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $consoleRoot = '/Users/jmolivas/develop/drupal/sites/drupal-project/vendor/drupal/console/';

        echo 'loadServices' . $consoleRoot . PHP_EOL;

        $loader = new YamlFileLoader($container, new FileLocator($consoleRoot));
        $loader->load('services.yml');

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(sprintf('%s/config/services/', $consoleRoot));
        foreach ($finder as $file) {
            $loader->load($file->getPathName());
        }
    }
}
