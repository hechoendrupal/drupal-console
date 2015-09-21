<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginFieldFormatterDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginFieldFormatterDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'bar', 'bar' . rand(), 'foo-bar']
        ];
    }
}
