<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ServiceGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class ServiceGenerator extends Generator
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
     * @param string $module       Module name
     * @param string $name         Service name
     * @param string $class        Class name
     * @param string $interface    If TRUE an interface for this service is generated
     * @param array  $services     List of services
     * @param string $path_service Path of services
     */
    public function generate($module, $name, $class, $interface, $interface_name, $services, $path_service)
    {
        $interface = $interface ? ($interface_name ?: $class . 'Interface') : false;
        $parameters = [
            'module' => $module,
            'name' => $name,
            'class' => $class,
            'class_path' => sprintf('Drupal\%s\%s', $module, $class),
            'interface' => $interface,
            'services' => $services,
            'path_service' => $path_service,
            'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];
       
        $this->renderFile(
            'module/services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml',
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
                $this->setDirectory($path_service, 'interface.php.twig', $module, $interface),
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
            $default_target = $this->extensionManager->getModule($module)->getPath() .'/src/'.$class.'.php';
            $custom_target = $this->extensionManager->getModule($module)->getPath() .'/'.$target.'/'.$class.'.php';

            $directory = (strcmp($target, $default_path) == 0) ? $default_target : $custom_target;
            break;
        case 'interface.php.twig':
            $default_target = $this->extensionManager->getModule($module)->getPath() .'/src/'.$class.'.php';
            $custom_target = $this->extensionManager->getModule($module)->getPath() .'/'.$target.'/'.$class.'.php';

            $directory = (strcmp($target, $default_path) == 0) ? $default_target : $custom_target;
            break;
        default:
            // code...
            break;
        }

        return $directory;
    }
}
