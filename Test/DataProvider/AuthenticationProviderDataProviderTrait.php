<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class AuthenticationProviderDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
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
          ['Foo', 'foo' . rand()],
        ];
    }
}
