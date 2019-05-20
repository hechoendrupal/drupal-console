<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ThemeRegionTrait.
 */

namespace Drupal\Console\Command\Shared;

trait UpdateTrait
{
    /**
     * @param $updates
     * @param $messageKey
     * @return mixed
     */
    public function showUpdateTable($updates, $messageKey)
    {
        if(!$updates) {
            return 1;
        }

        $this->getIo()->info($messageKey);
        $tableHeader = [
            $this->trans('commands.debug.update.messages.module'),
            $this->trans('commands.debug.update.messages.update-n'),
            $this->trans('commands.debug.update.messages.description')
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
        $this->getIo()->table($tableHeader, $tableRows);
    }

    /**
     * @param $postUpdates
     * @param $messageKey
     * @return mixed
     */
    public function showPostUpdateTable($postUpdates, $messageKey)
    {
        if(!$postUpdates) {
            return 1;
        }

        $this->getIo()->info($messageKey);
        $tableHeader = [
            $this->trans('commands.debug.update.messages.module'),
            $this->trans('commands.debug.update.messages.post-update'),
            $this->trans('commands.debug.update.messages.description')
        ];

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
        $this->getIo()->table($tableHeader, $tableRows);
    }
}
