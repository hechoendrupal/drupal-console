<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\views\Entity\View;
use Drupal\Component\Serialization\Yaml;

class UpdateExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:execute')
            ->setDescription($this->trans('commands.update.debug.description'))
            ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument('update-n', InputArgument::OPTIONAL, $this->trans('commands.update.debug.options.update-n'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /*      drupal_set_installed_schema_version('sample', '8000');
        exit();*/

        include_once DRUPAL_ROOT . '/core/includes/install.inc';
        include_once DRUPAL_ROOT . '/core/includes/update.inc';

        $module = $input->getArgument('module');
        $update_n = $input->getArgument('update-n');

        $module_handler = $this->getModuleHandler();

        drupal_load_updates();
        update_fix_compatibility();

        $updates = update_get_update_list();
        if ($module != 'all') {
            if (!isset($updates[$module])) {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.update.execute.messages.no-module-updates'),
                        $module
                    )
                    . '</error>'
                );
                return;
            } else {
                // filter to execute only a specific module updates
                $updates = [$module => $updates[$module]];

                if ($update_n && !isset($updates[$module]['pending'][$update_n])) {
                    $output->writeln(
                        '[-] <info>' .
                        sprintf(
                            $this->trans('commands.update.execute.messages.module-update-function-not-found'),
                            $module,
                            $update_n
                        )
                        . '</info>'
                    );
                }
            }
        }


        $output->writeln(
            '[-] <info>' .
            $this->trans('commands.site.maintenance.description')
            . '</info>'
        );
        \Drupal::state()->set('system.maintenance_mode', true);

        foreach ($updates as $module_name => $module_updates) {
            foreach ($module_updates['pending'] as $update_number => $update) {
                if ($module != 'all' && $update_n != null and $update_n != $update_number) {
                    continue;
                }

                //Executing all pending updates
                if ($update_n > $module_updates['start']) {
                    $output->writeln(
                        '[-] <info>' .
                        $this->trans('commands.update.execute.messages.executing-required-previous-updates')
                        . '</info>'
                    );
                }
                for ($update_index=$module_updates['start']; $update_index<=$update_number; $update_index++) {
                    $output->writeln(
                        '[-] <info>' .
                        sprintf(
                            $this->trans('commands.update.execute.messages.executing-update'),
                            $update_index,
                            $module_name
                        )
                        . '</info>'
                    );

                    try {
                        $module_handler->invoke($module_name, 'update_'  . $update_index);
                    } catch (\Exception $e) {
                        watchdog_exception('update', $e);
                        $output->writeln(
                            '<error>' .
                            $e->getMessage() .
                            '</error>'
                        );
                    }

                    //Update module schema version
                    drupal_set_installed_schema_version($module_name, $update_index);
                }
            }
        }

        \Drupal::state()->set('system.maintenance_mode', false);
        $output->writeln(
            '[-] <info>' .
            $this->trans('commands.site.maintenance.messages.maintenance-off')
            . '</info>'
        );

        $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
