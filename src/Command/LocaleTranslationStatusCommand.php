<?php

/**
 * @file
 * Contains \Drupal\Console\Command\MigrateDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocaleTranslationStatusCommand extends ContainerAwareCommand
{
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
        $language = $input->getArgument('language');

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        $this->displayUpdates($language, $output, $table);
    }

    protected function displayUpdates($language_filter, $output, $table)
    {

        $table->setHeaders(
            [
                $this->trans('commands.locale.translation.status.messages.project'),
                $this->trans('commands.locale.translation.status.messages.version'),
                $this->trans('commands.locale.translation.status.messages.local-age'),
                $this->trans('commands.locale.translation.status.messages.remote-age'),
                $this->trans('commands.locale.translation.status.messages.info'),
            ]
        );

        $languages = locale_translatable_language_list();
        $status = locale_translation_get_status();

        $this->getModuleHandler()->loadInclude('locale', 'compare.inc');

        $project_data = locale_translation_build_projects();

        if (!$languages) {
            $output->writeln('[+] <info>'.$this->trans('commands.locale.translation.status.messages.no-languages') .'</info>');

            return;
        }
        elseif (empty($status)) {
            $output->writeln('[+] <info>'.$this->trans('commands.locale.translation.status.messages.no-translations') .'</info>');
            return;

        }

        if ($languages && $status) {
            $table->setlayout($table::LAYOUT_COMPACT);

            $status_report = [];
            foreach ($status as $project_id => $project) {
                foreach ($project as $langcode => $project_info) {

                    $info = $this->createInfoString($project_info);

                    if ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE) {
                        $local = isset($project_info->files[LOCALE_TRANSLATION_LOCAL]) ? $project_info->files[LOCALE_TRANSLATION_LOCAL] : NULL;
                        $remote = isset($project_info->files[LOCALE_TRANSLATION_REMOTE]) ? $project_info->files[LOCALE_TRANSLATION_REMOTE] : NULL;

                        // Remove info because type was found
                        $info = '';
                    }

                    $local_age = $local->timestamp? \Drupal::service('date.formatter')->formatTimeDiffSince($local->timestamp): '';
                    $remote_age = $remote->timestamp? \Drupal::service('date.formatter')->formatTimeDiffSince($remote->timestamp): '';
                    $project_name = $project_info->name == 'drupal' ? $this->trans('commands.common.messages.drupal-core') : $project_data[$project_info->name]->info['name'];
                    $status_report[$langcode][] = [$project_name, $project_info->version, $local_age, $remote_age ,$info ];
                }
            }

            print $language_filter;

            foreach ($status_report as $langcode => $rows ) {
                if($language_filter !='' and !($language_filter == $langcode || strtolower($language_filter) == strtolower($languages[$langcode]->getName()))) {
                    continue;
                }
                $output->writeln('[+] <info>'.$languages[$langcode]->getName() .'</info>');
                foreach($rows as $row) {
                    $table->addRow($row);
                }
            }
        }

        $table->render($output);
    }

    /**
     * Provides debug info for projects in case translation files are not found.
     *
     * Translations files are being fetched either from Drupal translation server
     * and local files or only from the local filesystem depending on the
     * "Translation source" setting at admin/config/regional/translate/settings.
     * This method will produce debug information including the respective path(s)
     * based on this setting.
     *
     * Translations for development versions are never fetched, so the debug info
     * for that is a fixed message.
     *
     * @param array $project_info
     *   An array which is the project information of the source.
     *
     * @return string
     *   The string which contains debug information.
     */
    protected function createInfoString($project_info) {
        $remote_path = isset($project_info->files['remote']->uri) ? $project_info->files['remote']->uri : FALSE;
        $local_path = isset($project_info->files['local']->uri) ? $project_info->files['local']->uri : FALSE;

        if (strpos($project_info->version, 'dev') !== FALSE) {
            return $this->trans('commands.locale.translation.status.messages.no-translation-files');
        }
        if (locale_translation_use_remote_source() && $remote_path && $local_path) {
            return sprintf(
                $this->trans('commands.locale.translation.status.messages.file-not-found'),
                $remote_path,
                $local_path);
        }
        elseif ($local_path) {
            return
                sprintf(
                    $this->trans('commands.locale.translation.status.messages.local-file-not-found'),
                    $local_path);
        }

        return $this->trans('commands.locale.translation.status.messages.translation-not-determined');
    }
}
