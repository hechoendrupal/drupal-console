<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\MaintenanceCommand.
 */

namespace Drupal\Console\Command\Site;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\State\StateInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends Command
{
    /**
     * @var StateInterface
     */
    protected $state;


    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * DebugCommand constructor.
     *
     * @param StateInterface $state
     * @param ChainQueue     $chainQueue
     */
    public function __construct(
        StateInterface $state,
        ChainQueue $chainQueue
    ) {
        $this->state = $state;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('site:maintenance')
            ->setDescription($this->trans('commands.site.maintenance.description'))
            ->addArgument(
                'mode',
                InputArgument::REQUIRED,
                $this->trans('commands.site.maintenance.arguments.mode')
            )
            ->setAliases(['sma']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = $input->getArgument('mode');
        $stateName = 'system.maintenance_mode';
        $modeMessage = null;
        $cacheRebuild = true;

        if ('ON' === strtoupper($mode)) {
            $this->state->set($stateName, true);
            $modeMessage = 'commands.site.maintenance.messages.maintenance-on';
        }
        else if ('OFF' === strtoupper($mode)) {
            $this->state->set($stateName, false);
            $modeMessage = 'commands.site.maintenance.messages.maintenance-off';
        }

        if ($modeMessage === null) {
            $modeMessage = 'commands.site.maintenance.errors.invalid-mode';
            $cacheRebuild = false;
        }

        $this->getIo()->info($this->trans($modeMessage));

        if ($cacheRebuild) {
            $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
        }
    }
}
