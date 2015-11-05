<?php

/**
 * @file
 * Contains Drupal\Console\Helper\ShellHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
use Drupal\Console\Shell;

class ShellHelper extends Helper
{
    /**
     * @var Shell
     */
    protected $shell;

    /**
     * @param Shell $shell
     */
    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'shell';
    }

    /**
     * @return Shell
     */
    public function getShell()
    {
        return $this->shell;
    }
}
