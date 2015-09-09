<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginTypeYamlDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginTypeYamlDataProviderTrait
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
