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
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, false, null],
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', true, false, null],
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, false, null],
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', true, true, null],
        ];
    }
}
