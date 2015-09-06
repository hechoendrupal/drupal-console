<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class PermissionDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
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
