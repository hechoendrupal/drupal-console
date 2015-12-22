<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\ExecuteCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class ExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:execute')
            ->setDescription($this->trans('commands.update.execute.description'))
            ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument('update-n', InputArgument::OPTIONAL, $this->trans('commands.update.execute.options.update-n'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->getDrupalHelper()->loadLegacyFile('/core/includes/install.inc');
        $this->getDrupalHelper()->loadLegacyFile('/core/includes/update.inc');

        $module = $input->getArgument('module');
        $update_n = $input->getArgument('update-n');

        $module_handler = $this->getModuleHandler();

        drupal_load_updates();
        update_fix_compatibility();

        $updates = update_get_update_list();
        if ($module != 'all') {
            if (!isset($updates[$module])) {
                $io->error(
                    sprintf(
                        $this->trans('commands.update.execute.messages.no-module-updates'),
                        $module
                    )
                );
                return;
            } else {
                // filter to execute only a specific module updates
                $updates = [$module => $updates[$module]];

                if ($update_n && !isset($updates[$module]['pending'][$update_n])) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.update.execute.messages.module-update-function-not-found'),
                            $module,
                            $update_n
                        )
                    );
                }
            }
        }

        $io->info($this->trans('commands.site.maintenance.description'));

        $state = $this->hasGetService('state');
        $state->set('system.maintenance_mode', true);

        foreach ($updates as $module_name => $module_updates) {
            foreach ($module_updates['pending'] as $update_number => $update) {
                if ($module != 'all' && $update_n !== null && $update_n != $update_number) {
                    continue;
                }

                //Executing all pending updates
                if ($update_n > $module_updates['start']) {
                    $io->info($this->trans('commands.update.execute.messages.executing-required-previous-updates'));
                }
                for ($update_index=$module_updates['start']; $update_index<=$update_number; $update_index++) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.update.execute.messages.executing-update'),
                            $update_index,
                            $module_name
                        )
                    );

                    try {
                        $module_handler->invoke($module_name, 'update_'  . $update_index);
                    } catch (\Exception $e) {
                        watchdog_exception('update', $e);
                        $io->error($e->getMessage());
                    }

                    //Update module schema version
                    drupal_set_installed_schema_version($module_name, $update_index);
                }
            }
        }

        $state->set('system.maintenance_mode', false);
        $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
