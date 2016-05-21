<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PluginCKEditorButtonDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PluginCKEditorButtonDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo',  'foo' . rand(), 'foo', 'bar', 'Baz', 'foo/js/pluggin/bar/images/icon.png'],
        ];
    }
}
