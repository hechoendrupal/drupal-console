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
            'Valid provider' => [
                'module' => 'foo',
                'bundle name' => 'default_type',
                'bundle title' => 'default',
            ]
        ];
    }
}
