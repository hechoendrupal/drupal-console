<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class EntityContentDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait EntityContentDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Bar', 'bar', 'admin/structure', 'true'],
        ];
    }
}
