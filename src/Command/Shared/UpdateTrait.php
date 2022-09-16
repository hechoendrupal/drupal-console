<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ThemeRegionTrait.
 */

namespace Drupal\Console\Command\Shared;

trait UpdateTrait
{
    /**
     * @param array $updates
     * @param string $messageKey
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
     * @param array $postUpdates
     * @param string $messageKey
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

    /**
     * @param array $composerUpdates
     * @param boolean $onlyDrupal
     * @param string $messageKey
     * @return mixed
     */
    public function showComposerUpdateTable($composerUpdates, $onlyDrupal, $messageKey)
    {
        if(!$composerUpdates) {
            return 1;
        }

        $this->getIo()->info($messageKey);
        $tableHeader = [
            $this->trans('commands.debug.update.composer.messages.name'),
            $this->trans('commands.debug.update.composer.messages.current-version'),
            $this->trans('commands.debug.update.composer.messages.latest-version'),
            $this->trans('commands.debug.update.composer.messages.description')
        ];

        $tableRows = [];
        foreach ($composerUpdates as $key => $values) {
            if($onlyDrupal){
                if(strpos($values->name, 'drupal/') === false ){
                    continue;
                }
            }

            $tableRows[] = [
                $values->name,
                $values->version,
                $values->latest,
                $values->description,
            ];
        }
        $this->getIo()->table($tableHeader, $tableRows);
    }
}
