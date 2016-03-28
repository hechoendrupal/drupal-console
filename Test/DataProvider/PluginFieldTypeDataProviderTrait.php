<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginFieldTypeDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginFieldTypeDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'foo-bar', 'foo-bar', 'bar', 'Foo-Bar']
        ];
    }
}
