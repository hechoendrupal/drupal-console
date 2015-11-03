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

        $db = $connectionInfo[$database];
        if ($db['driver'] !== 'mysql') {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.database.connect.messages.database-not-supported'),
                    $db['driver']
                )
            );
            return;
        }

        $connection = sprintf(
            '%s -A --database=%s --user=%s --password=%s --host=%s --port=%s',
            $db['driver'],
            $db['database'],
            $db['username'],
            $db['password'],
            $db['host'],
            $db['port']
        );

        return $connection;
    }
}
