<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class PermissionDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait PermissionDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'my permissions'],
        ];
    }
}
