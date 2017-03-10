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
class DebugCommand extends Command
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
     * DebugCommand constructor.
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
            $io->info($key);
            $io->writeln(Yaml::encode($this->state->get($key)));

            return 0;
        }

        $tableHeader = [$this->trans('commands.state.debug.messages.key')];
        $keyStoreStates = array_keys($this->keyValue->get('state')->getAll());
        $io->table($tableHeader, $keyStoreStates);

        return 0;
    }
}
