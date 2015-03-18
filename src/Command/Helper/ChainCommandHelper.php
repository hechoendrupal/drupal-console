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
     * @param $inputs array
     */
    public function addCommand($name, $inputs = [])
    {
        array_unshift($inputs, ['command' => $name]);
        $this->commands[] = ['name' => $name, 'inputs' => $inputs];
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
