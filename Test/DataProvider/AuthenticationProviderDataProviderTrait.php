<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class AuthenticationProviderDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait AuthenticationProviderDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
            'Valid provider' => [
                'module' => 'Foo',
                'class' => 'foo' . rand(),
                'provider ID' => 0
            ],
        ];
    }
}
