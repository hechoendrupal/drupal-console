<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ServiceGenerator.
 */

namespace Drupal\Console\Generator;

class ServiceGenerator extends Generator
{
    /**
     * Generator Service.
     *
     * @param string $module    Module name
     * @param string $name      Service name
     * @param string $class     Class name
     * @param string $interface If TRUE an interface for this service is generated
     * @param array  $services  List of services
     */
    public function generate($module, $name, $class, $interface, $services)
    {
        $parameters = [
            'module' => $module,
            'name' => $name,
            'class' => $class,
            'class_path' => sprintf('Drupal\%s\%s', $module, $class),
            'interface' => $interface,
            'services' => $services,
            'file_exists' => file_exists($this->getSite()->getModulePath($module).'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/services.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/service.php.twig',
            $this->getSite()->getModulePath($module).'/src/'.$class.'.php',
            $parameters
        );

        if ($interface) {
            $this->renderFile(
                'module/src/service-interface.php.twig',
                $this->getSite()->getModulePath($module).'/src/'.$class.'Interface.php',
                $parameters
            );
        }
    }
}
