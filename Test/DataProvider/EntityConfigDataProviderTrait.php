<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class EntityConfigDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait EntityConfigDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['Foo', 'foo' . rand(), 'Bar', '', 'bar', 'admin/structure'],
        ];
    }
}
