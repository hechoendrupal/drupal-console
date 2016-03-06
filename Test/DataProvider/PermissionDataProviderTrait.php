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
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'my permissions'],
        ];
    }
}
