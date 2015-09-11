<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class EntityContentDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait EntityContentDataProviderTrait
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
