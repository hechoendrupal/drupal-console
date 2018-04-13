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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $class = $parameters['class'];
        $path_service = $parameters['path_service'];

        $parameters['interface'] = $parameters['interface'] ? ($parameters['interface_name'] ?: $class . 'Interface') : false;
        $interface = $parameters['interface'];
        $moduleServiceYaml = $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.services.yml';
        $parameters['class_path'] = sprintf('Drupal\%s\%s', $module, $class);
        $parameters['file_exists'] = file_exists($moduleServiceYaml);
       
        $this->renderFile(
            'module/services.yml.twig',
            $moduleServiceYaml,
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
        $modulePath = $this->extensionManager->getModule($module)->getPath();

        switch ($template) {
            case 'service.php.twig':
            case 'interface.php.twig':
                $default_target = $modulePath . '/src/' . $class . '.php';
                $custom_target = $modulePath . '/' . $target . '/' . $class . '.php';

                $directory = (strcmp($target, $default_path) == 0) ? $default_target : $custom_target;
            break;
        default:
            // code...
            break;
        }

        return $directory;
    }
}
