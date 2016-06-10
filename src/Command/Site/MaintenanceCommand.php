<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\MaintenanceCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class MaintenanceCommand extends Command
{
    use ContainerAwareCommandTrait;

    protected function configure()
    {
        $this
            ->setName('site:maintenance')
            ->setDescription($this->trans('commands.site.maintenance.description'))
            ->addArgument(
                'mode',
                InputArgument::REQUIRED,
                $this->trans('commands.site.maintenance.arguments.mode').'[on/off]'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $state = $this->getDrupalService('state');

        $mode = $input->getArgument('mode');
        $stateName = 'system.maintenance_mode';
        $modeMessage = null;
        $cacheRebuild = true;

        if ('ON' === strtoupper($mode)) {
            $state->set($stateName, true);
            $modeMessage = 'commands.site.maintenance.messages.maintenance-on';
        }
        if ('OFF' === strtoupper($mode)) {
            $state->set($stateName, false);
            $modeMessage = 'commands.site.maintenance.messages.maintenance-off';
        }

        if ($modeMessage === null) {
            $modeMessage = 'commands.site.maintenance.errors.invalid-mode';
            $cacheRebuild = false;
        }

        $io->info($this->trans($modeMessage));

        if ($cacheRebuild) {
            $this->get('chain_queue')
                ->addCommand('cache:rebuild', ['cache' => 'all']);
        }
    }
}
