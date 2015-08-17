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

        $routes = [
          ['title' => 'Foo Controller', 'method' => 'index', 'route' => 'index']
        ];

        return [
          ['foo', 'FooController', $routes, true, null, 'foo_controller'],
          ['foo', 'FooController', $routes, false, null, 'foo_controller'],

        ];
    }
}
