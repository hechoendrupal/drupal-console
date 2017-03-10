<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\DebugCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Style\DrupalStyle;

class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var UpdateRegistry
     */
    protected $postUpdateRegistry;

    /**
     * DebugCommand constructor.
     *
     * @param Site           $site
     * @param UpdateRegistry $postUpdateRegistry
     */
    public function __construct(
        Site $site,
        UpdateRegistry $postUpdateRegistry
    ) {
        $this->site = $site;
        $this->postUpdateRegistry = $postUpdateRegistry;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('update:debug')
            ->setDescription($this->trans('commands.update.debug.description'));
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/install.inc');

        drupal_load_updates();
        update_fix_compatibility();

        $requirements = update_check_requirements();
        $severity = drupal_requirements_severity($requirements);
        $updates = update_get_update_list();

        $io->newLine();

        if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING)) {
            $this->populateRequirements($io, $requirements);
        } elseif (empty($updates)) {
            $io->info($this->trans('commands.update.debug.messages.no-updates'));
        } else {
            $this->populateUpdate($io, $updates);
            $this->populatePostUpdate($io);
        }
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param $requirements
     */
    private function populateRequirements(DrupalStyle $io, $requirements)
    {
        $io->info($this->trans('commands.update.debug.messages.requirements-error'));

        $tableHeader = [
          $this->trans('commands.update.debug.messages.severity'),
          $this->trans('commands.update.debug.messages.title'),
          $this->trans('commands.update.debug.messages.value'),
          $this->trans('commands.update.debug.messages.description'),
        ];

        $tableRows = [];
        foreach ($requirements as $requirement) {
            $minimum = in_array(
                $requirement['minimum schema'],
                [REQUIREMENT_ERROR, REQUIREMENT_WARNING]
            );
            if ((isset($requirement['minimum schema'])) && ($minimum)) {
                $tableRows[] = [
                  $requirement['severity'],
                  $requirement['title'],
                  $requirement['value'],
                  $requirement['description'],
                ];
            }
        }

        $io->table($tableHeader, $tableRows);
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param $updates
     */
    private function populateUpdate(DrupalStyle $io, $updates)
    {
        $io->info($this->trans('commands.update.debug.messages.module-list'));
        $tableHeader = [
          $this->trans('commands.update.debug.messages.module'),
          $this->trans('commands.update.debug.messages.update-n'),
          $this->trans('commands.update.debug.messages.description')
        ];
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
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     */
    private function populatePostUpdate(DrupalStyle $io)
    {
        $io->info(
            $this->trans('commands.update.debug.messages.module-list-post-update')
        );
        $tableHeader = [
          $this->trans('commands.update.debug.messages.module'),
          $this->trans('commands.update.debug.messages.post-update'),
          $this->trans('commands.update.debug.messages.description')
        ];

        $postUpdates = $this->postUpdateRegistry->getPendingUpdateInformation();
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
