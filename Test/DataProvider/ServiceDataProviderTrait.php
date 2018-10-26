<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class ServiceDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait ServiceDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Foo', false, [],'default'],
        ];
    }
}
