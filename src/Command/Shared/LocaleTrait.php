<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\LocaleTrait.
 */

namespace Drupal\Console\Command\Shared;

trait LocaleTrait
{
    protected function getLanguages()
    {
        $drupal = $this->getDrupalHelper();
        $languages = $drupal->getStandardLanguages();

        return $languages;
    }

    protected function getDefaultLanguage()
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        return $config->get('application.language');
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
    protected function createInfoString($project_info)
    {
        $remote_path = isset($project_info->files['remote']->uri) ? $project_info->files['remote']->uri : false;
        $local_path = isset($project_info->files['local']->uri) ? $project_info->files['local']->uri : false;

        if (strpos($project_info->version, 'dev') !== false) {
            return $this->trans('commands.locale.translation.status.messages.no-translation-files');
        }
        if (locale_translation_use_remote_source() && $remote_path && $local_path) {
            return sprintf(
                $this->trans('commands.locale.translation.status.messages.file-not-found'),
                $local_path,
                $remote_path
            );
        } elseif ($local_path) {
            return
                sprintf(
                    $this->trans('commands.locale.translation.status.messages.local-file-not-found'),
                    $local_path
                );
        }

        return $this->trans('commands.locale.translation.status.messages.translation-not-determined');
    }

    protected function projectsStatus()
    {
        $status_report = [];
        $status = locale_translation_get_status();
        foreach ($status as $project_id => $project) {
            foreach ($project as $langcode => $project_info) {
                $info = '';
                if ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE) {
                    $local = isset($project_info->files[LOCALE_TRANSLATION_LOCAL]) ? $project_info->files[LOCALE_TRANSLATION_LOCAL] : null;
                    $remote = isset($project_info->files[LOCALE_TRANSLATION_REMOTE]) ? $project_info->files[LOCALE_TRANSLATION_REMOTE] : null;
                    $local_age = $local->timestamp? format_date($local->timestamp, 'html_date'): '';
                    $remote_age = $remote->timestamp? format_date($remote->timestamp, 'html_date'): '';

                    if ($local_age >= $remote_age) {
                        $info = $this->trans('commands.locale.translation.status.messages.translation-project-updated');
                    }
                } else {
                    $local_age = '';
                    $remote_age = '';
                    $info = $this->createInfoString($project_info);
                }

                $status_report[$langcode][] = [$project_info->name, $project_info->version, $local_age, $remote_age ,$info ];
            }
        }

        return $status_report;
    }
}
