<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginBlockDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginBlockDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar', null, '', 'inputs'],
        ];
    }
}
