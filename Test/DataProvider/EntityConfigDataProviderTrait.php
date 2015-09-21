<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class EntityConfigDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait EntityConfigDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Bar', 'bar'],
        ];
    }
}
