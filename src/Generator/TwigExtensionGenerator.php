<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\TwigExtensionGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class TwigExtensionGenerator
 *
 * @package Drupal\Console\Generator
 */
class TwigExtensionGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
   * Generator Service.
   *
   * @param string $module   Module name
   * @param string $name     Service name
   * @param string $class    Class name
   * @param array  $services List of services
   */
    public function generate($module, $name, $class, $services)
    {
        $parameters = [
        'module' => $module,
        'name' => $name,
        'class' => $class,
        'class_path' => sprintf('Drupal\%s\TwigExtension\%s', $module, $class),
        'services' => $services,
        'tags' => ['name' => 'twig.extension'],
        'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/TwigExtension/twig-extension.php.twig',
            $this->extensionManager->getModule($module)->getPath() .'/src/TwigExtension/'.$class.'.php',
            $parameters
        );
    }
}
