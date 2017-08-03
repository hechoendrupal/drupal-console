<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\DebugCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Component\Serialization\Yaml;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class StateCommand extends Command
{
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
            ->setName('debug:state')
            ->setDescription($this->trans('commands.debug.state.description'))
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.state.arguments.key')
            )
            ->setHelp($this->trans('commands.debug.state.help'))
            ->setAliases(['dst']);
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

        $tableHeader = [$this->trans('commands.debug.state.messages.key')];
        $keyStoreStates = array_keys($this->keyValue->get('state')->getAll());
        $io->table($tableHeader, $keyStoreStates);

        return 0;
    }
}
