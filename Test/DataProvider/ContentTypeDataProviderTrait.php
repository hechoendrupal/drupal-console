<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class ContentTypeDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait ContentTypeDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();
        
        return [
          ['foo', 'default', 'default']
        ];
    }
}