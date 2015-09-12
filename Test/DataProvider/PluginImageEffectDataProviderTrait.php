<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginImageEffectkDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
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
