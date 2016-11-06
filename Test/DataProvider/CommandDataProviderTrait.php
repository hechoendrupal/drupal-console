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
            'Container aware' => [
                'module' => 'command_' . rand(),
                'name' => 'command:default',
                'class' => 'CommandDefault',
                'is container aware?' => false
            ],
            'Non container aware' => [
                'module' => 'command_' . rand(),
                'name' => 'command:default',
                'class' => 'CommandDefault',
                'is container aware?' => true,
            ]
        ];
    }
}
