<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class AuthenticationProviderDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait AuthenticationProviderDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), 0],
        ];
    }
}
