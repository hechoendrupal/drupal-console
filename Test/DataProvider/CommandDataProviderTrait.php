<?php

namespace Drupal\AppConsole\Test\DataProvider;

/**
 * Class CommandDataProviderTrait
 * @package Drupal\AppConsole\Test\DataProvider
 */
trait CommandDataProviderTrait
{
    /**
     * @return array
     */
    public function commandData()
    {
        $this->setUpTemporalDirectory();

        return [
            ['command_' . rand(), 'command:default', 'CommandDefault', false],
            ['command_' . rand(), 'command:default', 'CommandDefault', true]
        ];
    }
}
