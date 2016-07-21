<?php

namespace Drupal\Console\Utils;

use Drupal\Core\DrupalKernel as DrupalKernelBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;

/**
 * Class DrupalKernel
 * @package Drupal\Console\Utils
 */
class DrupalKernel extends DrupalKernelBase
{
    /**
     * @var ServiceModifierInterface[]
     */
    protected $serviceModifiers = [];

    /**
     * @inheritdoc
     */
    public static function createFromRequest(Request $request, $class_loader, $environment, $allow_dumping = true)
    {
        $kernel = new static($environment, $class_loader, $allow_dumping);
        static::bootEnvironment();
        $kernel->initializeSettings($request);
        return $kernel;
    }

    /**
     * @param \Drupal\Core\DependencyInjection\ServiceModifierInterface $serviceModifier
     */
    public function addServiceModifier(ServiceModifierInterface $serviceModifier)
    {
        $this->serviceModifiers[] = $serviceModifier;
    }

    /**
     * @inheritdoc
     */
    protected function getContainerBuilder()
    {
        $container = parent::getContainerBuilder();
        foreach ($this->serviceModifiers as $serviceModifier) {
            $serviceModifier->alter($container);
        }
        return $container;
    }

    public function loadServices($consoleRoot)
    {
        echo 'loadServices' . PHP_EOL;

        $container = parent::getContainerBuilder();
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
