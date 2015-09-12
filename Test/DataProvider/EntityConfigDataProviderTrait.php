<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class EntityConfigDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
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
