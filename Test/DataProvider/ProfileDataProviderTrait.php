<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class ProfileDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait ProfileDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          // Profile name, machine name, description, core version,
          // dependencies, distribution name.
          ['Foo', 'foo' . rand(), $this->dir . '/profiles', 'Description', '8.x', null, false],
          ['Foo', 'foo' . rand(), $this->dir . '/profiles', 'Description', '8.x', null, 'Bar'],
        ];
    }
}
