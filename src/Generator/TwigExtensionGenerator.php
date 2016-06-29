<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\TwigExtensionGenerator.
 */

namespace Drupal\Console\Generator;

/**
 * Class TwigExtensionGenerator
 * @package Drupal\Console\Generator
 */
class TwigExtensionGenerator extends Generator
{
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
        'file_exists' => file_exists($this->getSite()->getModulePath($module).'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/services.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/TwigExtension/twig-extension.php.twig',
            $this->getSite()->getModulePath($module).'/src/TwigExtension/'.$class.'.php',
            $parameters
        );
    }
}
