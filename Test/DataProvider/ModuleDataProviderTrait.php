<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class ModuleDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait ModuleDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, null],
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, null],
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', false, null],
          ['Foo', 'foo' . rand(), $this->dir, 'Description', '8.x', 'Other', true, null],
        ];
    }
}
