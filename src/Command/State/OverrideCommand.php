<?php

/**
 * @file
 * Contains \Drupal\Console\Command\State\OverrideCommand.
 */

namespace Drupal\Console\Command\State;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Component\Serialization\Yaml;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\State
 */
class OverrideCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('state:override')
            ->setDescription($this->trans('commands.state.debug.description'))
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                $this->trans('commands.state.override.arguments.key')
            )
            ->addArgument(
                'value',
                InputArgument::OPTIONAL,
                $this->trans('commands.state.override.arguments.value')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        if (!$key) {
            $io->error($this->trans('commands.state.override.errors.no-key'));
        }

        if (!$value) {
            $io->error($this->trans('commands.state.override.errors.no-value'));
        }

        if ($key && $value) {
            $state = $this->getState();
            $originalValue = Yaml::encode($state->get($key));
            $overrideValue = is_array($value)?Yaml::encode($value):$value;
            $state->set($key, $overrideValue);
            $tableHeaders = [
                $this->trans('commands.state.override.messages.key'),
                $this->trans('commands.state.override.messages.original'),
                $this->trans('commands.state.override.messages.override')
            ];

            $tableRows[] = [$key, $originalValue, $overrideValue];

            $io->table(
                $tableHeaders,
                $tableRows
            );
        }
    }
}
