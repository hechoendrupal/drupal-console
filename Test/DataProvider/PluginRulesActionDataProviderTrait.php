<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginRulesActionDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginRulesActionDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Foo', 'pluginID' . rand(), 'category', 'context'],
        ];
    }
}