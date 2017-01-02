<?php

namespace Drupal\Console\Command;

use Psy\Shell;
use Psy\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;

/**
 * Class ShellCommand
 *
 * @package Drupal\Console\Command
 */
class ShellCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('shell')
            ->setDescription($this->trans('commands.shell.description'))
            ->setHelp($this->trans('commands.shell.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = new Configuration;
        $shell = new Shell($config);
        $shell->run();
    }
}
