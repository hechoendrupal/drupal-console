<?php

/**
 * @file
 * Contains Drupal\Console\Utils\ChainQueue.
 */

namespace Drupal\Console\Utils;

/**
 * Class ChainCommandHelper
 * @package Drupal\Console\Helper
 */
class ChainQueue
{
    /**
 * @var $commands array 
*/
    private $commands;

    /**
     * @param $name             string
     * @param $inputs           array
     * @param $interactive      boolean
     * @param $learning         boolean
     */
    public function addCommand(
        $name,
        $inputs = [],
        $interactive = null,
        $learning = null
    ) {
        $inputs['command'] = $name;
        if (!is_null($learning)) {
            $inputs['--learning'] = $learning;
        }
        $this->commands[] =
            [
                'name' => $name,
                'inputs' => $inputs,
                'interactive' => $interactive
            ];
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
