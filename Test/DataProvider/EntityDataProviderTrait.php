<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class EntityDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait EntityDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'Bar', 'foo' . rand(), 'bar'],
        ];
    }
}
