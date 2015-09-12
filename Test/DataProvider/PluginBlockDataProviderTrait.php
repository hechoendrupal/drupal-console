<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginBlockDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginBlockDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar', null, 'inputs'],
        ];
    }
}
