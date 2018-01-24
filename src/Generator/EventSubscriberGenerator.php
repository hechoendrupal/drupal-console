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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $class = $parameters['class'];
        $moduleInstance = $this->extensionManager->getModule($module);
        $modulePath = $moduleInstance->getPath() . '/' . $module;
        $parameters = array_merge($parameters,
            [
          'class_path' => sprintf('Drupal\%s\EventSubscriber\%s', $module, $class),
          'tags' => ['name' => 'event_subscriber'],
          'file_exists' => file_exists($modulePath . '.services.yml'),
        ]);

        $this->renderFile(
            'module/src/event-subscriber.php.twig',
            $moduleInstance->getSourcePath() . '/EventSubscriber/' . $class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $modulePath . '.services.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
