<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginRestResourceDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginRestResourceDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Foo', 'pluginID' . rand(), 'url', 'states'],
        ];
    }
}