<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginImageFormatterkDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginImageFormatterDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar'],
        ];
    }
}
