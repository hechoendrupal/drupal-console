<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginFieldTypeDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginFieldTypeDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'foo-bar', 'foo-bar', 'bar', 'Foo-Bar']
        ];
    }
}
