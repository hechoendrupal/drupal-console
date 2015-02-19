<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Drupal\AppConsole\Console\Shell;

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
