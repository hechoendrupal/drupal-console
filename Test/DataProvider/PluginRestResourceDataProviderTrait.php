<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginRestResourceDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginRestResourceDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Foo', 'pluginID' . rand(), 'url', 'states'],
        ];
    }
}
