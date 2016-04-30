<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class ModuleDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait ModuleDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', sprintf('%s_%s', 'foo', rand()), $this->dir, 'Description', '8.x', 'Custom', true, '', false, null],
          ['Foo', sprintf('%s_%s', 'foo', rand()), $this->dir, 'Description', '8.x', 'Custom', false, 'default', false, null],
          ['Foo', sprintf('%s_%s', 'foo', rand()), $this->dir, 'Description', '8.x', 'Custom', true, '', false, null],
          ['Foo', sprintf('%s_%s', 'foo', rand()), $this->dir, 'Description', '8.x', 'Custom', false, '', false, null],
        ];
    }
}
