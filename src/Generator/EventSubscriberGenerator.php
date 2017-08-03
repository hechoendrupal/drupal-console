<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ServiceGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class EventSubscriberGenerator extends Generator
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
     * @param string $events
     * @param array  $services List of services
     */
    public function generate($module, $name, $class, $events, $services)
    {
        $parameters = [
          'module' => $module,
          'name' => $name,
          'class' => $class,
          'class_path' => sprintf('Drupal\%s\EventSubscriber\%s', $module, $class),
          'events' => $events,
          'services' => $services,
          'tags' => ['name' => 'event_subscriber'],
          'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/src/event-subscriber.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/EventSubscriber/'.$class.'.php',
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
