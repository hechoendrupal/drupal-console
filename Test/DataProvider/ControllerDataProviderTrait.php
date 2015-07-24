<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class ModuleDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait ControllerDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        $services = [
          'twig' => [
            'name' => 'twig',
            'machine_name' => 'twig',
            'class' => '\Twig_Environment',
            'short' => 'Twig_Environment',
          ]
        ];

        $routes = [
          ['title' => 'Foo Controller', 'method' => 'index', 'route' => 'index']
        ];

        return [
          // ToDo: send sercives, issues with Twig container get
          //['foo', 'FooController', $routes, true, $services, 'foo_controller'],
          ['foo', 'FooController', $routes, true, null, 'foo_controller'],
          ['foo', 'FooController', $routes, false, null, 'foo_controller'],

        ];
    }
}
