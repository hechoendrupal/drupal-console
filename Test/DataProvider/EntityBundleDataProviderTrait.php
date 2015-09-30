<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class EntityBundleDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait EntityBundleDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();
        
        return [
          ['foo', 'default_type', 'default']
        ];
    }
}
