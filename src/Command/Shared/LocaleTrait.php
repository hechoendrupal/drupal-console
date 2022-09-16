<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\LocaleTrait.
 */

namespace Drupal\Console\Command\Shared;

trait LocaleTrait
{
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

    /**
     * LOCALE_TRANSLATION_REMOTE
     * and LOCALE_TRANSLATION_LOCAL indicate available new translations,
     * LOCALE_TRANSLATION_CURRENT indicate that the current translation is them
     * most recent.
     */
    protected function projectsStatus()
    {
        $status_report = [];
        $status = locale_translation_get_status();
        $date_formatter = \Drupal::service('date.formatter');
        $date_format = 'html_date';
        foreach ($status as $project) {
            foreach ($project as $langcode => $project_info) {
                $info = '';
                if ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE) {
                    $local = isset($project_info->files[LOCALE_TRANSLATION_LOCAL]) ? $project_info->files[LOCALE_TRANSLATION_LOCAL] : null;
                    $remote = isset($project_info->files[LOCALE_TRANSLATION_REMOTE]) ? $project_info->files[LOCALE_TRANSLATION_REMOTE] : null;
                    $local_age = $local->timestamp? $date_formatter->format($local->timestamp, $date_format): '';
                    $remote_age = $remote->timestamp? $date_formatter->format($remote->timestamp, $date_format): '';

                    if ($local_age >= $remote_age) {
                        $info = $this->trans('commands.locale.translation.status.messages.translation-project-updated');
                    } else {
                        $info = $this->trans('commands.locale.translation.status.messages.translation-project-available');
                    }
                } elseif ($project_info->type == LOCALE_TRANSLATION_CURRENT) {
                    $info = $this->trans('commands.locale.translation.status.messages.translation-project-updated');
                } else {
                    $local_age = '';
                    $remote_age = '';
                    $info = $this->createInfoString($project_info);
                }

                $status_report[$langcode][] = [$project_info->name, $project_info->version, $local_age, $remote_age, $info ];
            }
        }

        return $status_report;
    }
}
