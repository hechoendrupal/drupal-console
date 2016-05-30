<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\DebugCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends Command
{
    use ContainerAwareCommandTrait;

    protected function configure()
    {
        $this
            ->setName('update:debug')
            ->setDescription($this->trans('commands.update.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->get('site')->loadLegacyFile('/core/includes/update.inc');
        $this->get('site')->loadLegacyFile('/core/includes/install.inc');
        $updateRegistry = $this->getDrupalService('update.post_update_registry');

        drupal_load_updates();
        update_fix_compatibility();

        $updates = update_get_update_list();
        $postUpdates = $updateRegistry->getPendingUpdateInformation();

        $requirements = update_check_requirements();
        $severity = drupal_requirements_severity($requirements);

        $io->newLine();

        if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING)) {
            $io->info($this->trans('commands.update.debug.messages.requirements-error'));

            $tableHeader = [
                $this->trans('commands.update.debug.messages.severity'),
                $this->trans('commands.update.debug.messages.title'),
                $this->trans('commands.update.debug.messages.value'),
                $this->trans('commands.update.debug.messages.description'),
            ];

            $tableRows = [];
            foreach ($requirements as $requirement) {
                if (isset($requirement['minimum schema']) & in_array($requirement['minimum schema'], array(REQUIREMENT_ERROR, REQUIREMENT_WARNING))) {
                    $tableRows[] = [
                        $requirement['severity'],
                        $requirement['title'],
                        $requirement['value'],
                        $requirement['description'],
                    ];
                }
            }

            $io->table($tableHeader, $tableRows);

            return;
        }

        if (empty($updates)) {
            $io->info($this->trans('commands.update.debug.messages.no-updates'));

            return;
        }

        $tableHeader = [
            $this->trans('commands.update.debug.messages.module'),
            $this->trans('commands.update.debug.messages.update-n'),
            $this->trans('commands.update.debug.messages.description')
        ];

        $io->info($this->trans('commands.update.debug.messages.module-list'));

        $tableRows = [];
        foreach ($updates as $module => $module_updates) {
            foreach ($module_updates['pending'] as $update_n => $update) {
                list(, $description) = explode($update_n . " - ", $update);
                $tableRows[] = [
                    $module,
                    $update_n,
                    trim($description),
                ];
            }
        }

        $io->table($tableHeader, $tableRows);

        $tableHeader = [
          $this->trans('commands.update.debug.messages.module'),
          $this->trans('commands.update.debug.messages.post-update'),
          $this->trans('commands.update.debug.messages.description')
        ];

        $io->info($this->trans('commands.update.debug.messages.module-list-post-update'));

        $tableRows = [];
        foreach ($postUpdates as $module => $module_updates) {
            foreach ($module_updates['pending'] as $postUpdateFunction => $message) {
                $tableRows[] = [
                  $module,
                  $postUpdateFunction,
                  $message,
                ];
            }
        }

        $io->table($tableHeader, $tableRows);
    }
}
