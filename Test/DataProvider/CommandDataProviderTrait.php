<?php

namespace Drupal\Console\Test\DataProvider;

/**
 * Class CommandDataProviderTrait
 * @package Drupal\Console\Test\DataProvider
 */
trait CommandDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporaryDirectory();

        return [
            ['command_' . rand(), 'command:default', 'CommandDefault', false],
            ['command_' . rand(), 'command:default', 'CommandDefault', true]
        ];
    }
}
