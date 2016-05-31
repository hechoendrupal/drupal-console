<?php

/**
 * @file
 * Contains \Drupal\Console\Command\State\DebugCommand.
 */

namespace Drupal\Console\Command\State;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Component\Serialization\Yaml;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\State
 */
class DebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('state:debug')
            ->setDescription($this->trans('commands.state.debug.description'))
            ->setHelp($this->trans('commands.state.debug.help'))
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                $this->trans('commands.state.debug.arguments.key')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $key = $input->getArgument('key');

        if ($key) {
            $state = $this->getState();
            $io->info($key);
            $io->writeln(Yaml::encode($state->get($key)));

            return;
        }

        $tableHeader = [$this->trans('commands.state.debug.messages.key')];

        $keyValue = $this->getService('keyvalue');
        $keyStoreStates = array_keys($keyValue->get('state')->getAll());

        $io->table($tableHeader, $keyStoreStates);
    }
}
