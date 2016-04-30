<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ServiceGenerator.
 */

namespace Drupal\Console\Generator;

class EventSubscriberGenerator extends Generator
{
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
          'tags' => array('name' => 'event_subscriber'),
          'file_exists' => file_exists($this->getSite()->getModulePath($module).'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/src/event-subscriber.php.twig',
            $this->getSite()->getSourcePath($module).'/EventSubscriber/'.$class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $this->getSite()->getModulePath($module).'/'.$module.'.services.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
