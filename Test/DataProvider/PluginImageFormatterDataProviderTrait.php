<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PluginImageFormatterkDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait PluginImageFormatterDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar'],
        ];
    }
}
