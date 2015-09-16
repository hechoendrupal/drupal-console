<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginConditionDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginConditionDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar-id', 'foo-context-id', 'foo-context-label', false]
        ];
    }
}