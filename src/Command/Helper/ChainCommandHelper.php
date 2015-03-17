<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\ChainCommandHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class ChainCommandHelper extends Helper
{

    /** @var $commands array */
    private $commands;

    /**
     * @param $name      string
     * @param $arguments array
     */
    public function addCommand($name, $arguments)
    {
        array_unshift($arguments, ['command' => $name]);
        $this->commands[] = ['name' => $name, 'arguments' => $arguments];
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chain';
    }
}
