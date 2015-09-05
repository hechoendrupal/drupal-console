<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class FormDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait FormDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
          ['Foo', 'foo' . rand(), 'id' . rand(), null, 'inputs', false]
        ];
    }
}