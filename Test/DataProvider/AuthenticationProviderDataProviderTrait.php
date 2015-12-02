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
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'foo' . rand(), 0],
        ];
    }
}
