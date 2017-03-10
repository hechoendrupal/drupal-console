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
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Component\Serialization\Yaml;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\State
 */
class OverrideCommand extends Command
{
    use CommandTrait;

    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * @var KeyValueFactoryInterface
     */
    protected $keyValue;

    /**
     * OverrideCommand constructor.
     *
     * @param StateInterface           $state
     * @param KeyValueFactoryInterface $keyValue
     */
    public function __construct(
        StateInterface $state,
        KeyValueFactoryInterface $keyValue
    ) {
        $this->state = $state;
        $this->keyValue = $keyValue;
        parent::__construct();
    }


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
            $names = array_keys($this->keyValue->get('state')->getAll());
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

            return 1;
        }

        if (!$value) {
            $io->error($this->trans('commands.state.override.errors.no-value'));

            return 1;
        }

        if ($key && $value) {
            $originalValue = Yaml::encode($this->state->get($key));
            $overrideValue = is_array($value)?Yaml::encode($value):$value;
            $this->state->set($key, $overrideValue);
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

        return 0;
    }
}
