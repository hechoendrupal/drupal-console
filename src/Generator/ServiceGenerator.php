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
     * @param string $module       Module name
     * @param string $name         Service name
     * @param string $class        Class name
     * @param string $interface    If TRUE an interface for this service is generated
     * @param array  $services     List of services
     * @param string $path_service Path of services
     */
    public function generate($module, $name, $class, $interface, $services, $path_service)
    {
        $parameters = [
            'module' => $module,
            'name' => $name,
            'class' => $class,
            'class_path' => sprintf('Drupal\%s\%s', $module, $class),
            'interface' => $interface,
            'services' => $services,
            'path_service' => $path_service,
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
            $this->setDirectory($path_service, 'service.php.twig', $module, $class),
            $parameters
        );

        if ($interface) {
            $this->renderFile(
                'module/src/service-interface.php.twig',
                $this->setDirectory($path_service, 'interface.php.twig', $module, $class),
                $parameters
            );
        }
    }

    protected function setDirectory($target, $template, $module, $class)
    {
        $default_path = '/modules/custom/' . $module . '/src/';
        $directory = '';

        switch ($template) {
        case 'service.php.twig':
            $default_target = $this->getSite()->getModulePath($module).'/src/'.$class.'.php';
            $custom_target = $this->getSite()->getModulePath($module).'/'.$target.'/'.$class.'.php';

            $directory = (strcmp($target, $default_path) == 0) ? $default_target : $custom_target;
            break;
        case 'interface.php.twig':
            $default_target = $this->getSite()->getModulePath($module).'/src/'.$class.'Interface.php';
            $custom_target = $this->getSite()->getModulePath($module).'/'.$target.'/'.$class.'Interface.php';

            $directory = (strcmp($target, $default_path) == 0) ? $default_target : $custom_target;
            break;
        default:
            // code...
            break;
        }

        return $directory;
    }
}
