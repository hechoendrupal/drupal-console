<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginConditionDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginConditionDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar-id', 'foo-context-id', 'foo-context-label', false]
        ];
    }
}
