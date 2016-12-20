<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\RouteSubscriberGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;

class RouteSubscriberGenerator extends Generator
{
    /**
     * @var Manager  
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
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
     * @param string $module Module name
     * @param string $name   Service name
     * @param string $class  Class name
     */
    public function generate($module, $name, $class)
    {
        $parameters = [
          'module' => $module,
          'name' => $name,
          'class' => $class,
          'class_path' => sprintf('Drupal\%s\Routing\%s', $module, $class),
          'tags' => array('name' => 'event_subscriber'),
          'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/src/Routing/route-subscriber.php.twig',
            $this->extensionManager->getModule($module)->getRoutingPath().'/'.$class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
