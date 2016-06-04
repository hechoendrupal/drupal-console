<?php

/**
 * @file
 * Contains \Drupal\Console\Command\MigrateDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\LocaleTrait;

class TranslationStatusCommand extends ContainerAwareCommand
{
    use LocaleTrait;

    protected function configure()
    {
        $this
            ->setName('locale:translation:status')
            ->setDescription($this->trans('commands.locale.translation.status.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.locale.translation.status.arguments.language')
            );

        $this->addDependency('locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $language = $input->getArgument('language');
        $tableHeader = [
          $this->trans('commands.locale.translation.status.messages.project'),
          $this->trans('commands.locale.translation.status.messages.version'),
          $this->trans('commands.locale.translation.status.messages.local-age'),
          $this->trans('commands.locale.translation.status.messages.remote-age'),
          $this->trans('commands.locale.translation.status.messages.info'),
        ];


        $languages = locale_translatable_language_list();
        $status = locale_translation_get_status();

        $this->getModuleHandler()->loadInclude('locale', 'compare.inc');

        if (!$languages) {
            $io->info($this->trans('commands.locale.translation.status.messages.no-languages'));
            return;
        } elseif (empty($status)) {
            $io->info($this->trans('commands.locale.translation.status.messages.no-translations'));
            return;
        }
        if ($languages) {
            $projectsStatus = $this->projectsStatus();

            foreach ($projectsStatus as $langcode => $rows) {
                $tableRows = [];
                if ($language !='' && !($language == $langcode || strtolower($language) == strtolower($languages[$langcode]->getName()))) {
                    continue;
                }
                $io->info($languages[$langcode]->getName());
                foreach ($rows as $row) {
                    if ($row[0] == 'drupal') {
                        $row[0] = $this->trans('commands.common.messages.drupal-core');
                    }
                    $tableRows[] = $row;
                }
                $io->table($tableHeader, $tableRows, 'compact');
            }
        }
    }
}
