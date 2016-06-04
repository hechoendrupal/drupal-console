<?php

/**
 * @file
 * Contains \Drupal\Console\Command\State\OverrideCommand.
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
class OverrideCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('state:override')
            ->setDescription($this->trans('commands.state.override.description'))
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
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        if (!$key) {
            $keyValue = $this->getService('keyvalue');
            $names = array_keys($keyValue->get('state')->getAll());
            $key = $io->choiceNoList(
                $this->trans('commands.state.override.arguments.key'),
                $names
            );
            $input->setArgument('key', $key);
        }
        if (!$value) {
            $value = $io->ask(
                $this->trans('commands.state.override.arguments.value')
            );
            $input->setArgument('value', $value);
        }
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
            $state = $this->getService('state');
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
