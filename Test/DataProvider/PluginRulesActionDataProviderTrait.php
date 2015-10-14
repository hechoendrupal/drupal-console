<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginRulesActionDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
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
          ['Foo', 'foo' . rand(), 'Foo', 'pluginID' . rand(), 'category', 'context', 'bar'],
        ];
    }
}
