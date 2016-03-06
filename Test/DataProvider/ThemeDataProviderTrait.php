<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class ThemeDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait ThemeDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', rand(), $this->dir.'/themes/custom', 'bar', 'Other', '8.x', 'sd', 'global-styling', false, false]
        ];
    }
}
