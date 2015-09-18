<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginFieldWidgetDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginFieldWidgetDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'foo-bar', 'Foo-Bar']
        ];
    }
}
