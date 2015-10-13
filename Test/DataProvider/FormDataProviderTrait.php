<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class FormDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
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
          ['Foo', 'foo' . rand(), 'id' . rand(), null, null, true]
        ];
    }
}
