<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class ControllerDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait ControllerDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        $routes = [
          ['title' => 'Foo Controller', 'method' => 'index', 'route' => 'index']
        ];

        return [
          ['foo', 'FooController', $routes, true, null, 'foo_controller'],
          ['foo', 'FooController', $routes, false, null, 'foo_controller'],
        ];
    }
}
