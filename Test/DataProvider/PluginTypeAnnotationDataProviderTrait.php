<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginTypeAnnotationDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginTypeAnnotationDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'MyPlugin', 'my_plugin', 'my.plugin'],
        ];
    }
}
