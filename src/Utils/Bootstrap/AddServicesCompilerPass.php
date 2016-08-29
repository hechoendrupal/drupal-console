<?php

namespace Drupal\Console\Utils\Bootstrap;

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

        $_ = ( basename(dirname($this->root)) == "web" || basename(dirname($this->root)) == "docroot" )? "../":"";
       echo "hola"; print_r($_); print_r(dirname($this->root)); print_r(basename(dirname($this->root)));
        $loader->load($this->root. $_ . DRUPAL_CONSOLE_CORE . 'services.yml');
        $loader->load($this->root. $_ . DRUPAL_CONSOLE . 'services.yml');

        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in(sprintf('%s/config/services/', $this->root.DRUPAL_CONSOLE));
        foreach ($finder as $file) {
            $loader->load($file->getPathName());
        }

        $container->setParameter(
            'console.service_definitions',
            $container->getDefinitions()
        );
    }
}
