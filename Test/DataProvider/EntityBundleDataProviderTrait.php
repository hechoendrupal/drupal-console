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
        $this->setUpTemporaryDirectory();
        
        return [
          ['foo', 'default_type', 'default']
        ];
    }
}
