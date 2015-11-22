<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ConnectTrait.
 */

namespace Drupal\Console\Command\Database;

trait ConnectTrait
{
    public function resolveConnection($message, $database)
    {
        if (!$database) {
            $database = 'default';
        }

        $connectionInfo = $this->getConnectionInfo();

        if (!$connectionInfo || !isset($connectionInfo[$database])) {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.database.connect.messages.database-not-found'),
                    $database
                )
            );
            return;
        }

        $databaseConnection = $connectionInfo[$database];
        if ($databaseConnection['driver'] !== 'mysql') {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.database.connect.messages.database-not-supported'),
                    $databaseConnection['driver']
                )
            );
            return;
        }

        return $databaseConnection;
    }
}
