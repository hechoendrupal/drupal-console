<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class ServiceDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait ServiceDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Foo', false, null],
        ];
    }
}
