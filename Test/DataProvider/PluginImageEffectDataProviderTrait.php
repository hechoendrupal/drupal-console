<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginImageEffectkDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginImageEffectDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar', 'description'],
        ];
    }
}
