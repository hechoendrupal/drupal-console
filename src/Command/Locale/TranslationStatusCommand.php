<?php

/**
 * @file
 * Contains \Drupal\Console\Command\MigrateDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\LocaleTrait;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;

/**
 * @DrupalCommand(
 *     dependencies = {
 *         "locale"
 *     }
 * )
 */
class TranslationStatusCommand extends Command
{
    use LocaleTrait;
    use ContainerAwareCommandTrait;

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

        $this->getApplication()->getDrupalHelper()->loadLegacyFile($this->getApplication()->getSite()->getModulePath('locale') . '/locale.compare.inc');

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
