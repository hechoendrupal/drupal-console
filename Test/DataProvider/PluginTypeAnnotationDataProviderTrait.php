<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginTypeAnnotationDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginTypeAnnotationDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'MyPlugin', 'my_plugin', 'my.plugin'],
        ];
    }
}
