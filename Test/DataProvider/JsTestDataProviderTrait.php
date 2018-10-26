<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class JsTestDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait JsTestDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
          ['foo', 'JsFooTest'],
        ];
    }
}
