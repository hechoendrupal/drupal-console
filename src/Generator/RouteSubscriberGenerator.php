<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\RouteSubscriberGenerator.
 */

namespace Drupal\Console\Generator;

class RouteSubscriberGenerator extends Generator
{
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
          'file_exists' => file_exists($this->getSite()->getModulePath($module).'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/src/Routing/route-subscriber.php.twig',
            $this->getSite()->getRoutingPath($module).'/'.$class.'.php',
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
