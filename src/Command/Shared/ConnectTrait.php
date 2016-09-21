<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ConnectTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Database\Database;

trait ConnectTrait
{
    protected $supportedDrivers = array('mysql','pgsql');

    public function resolveConnection(DrupalStyle $io, $database = 'default')
    {
        $connectionInfo = Database::getConnectionInfo();

        if (!$connectionInfo || !isset($connectionInfo[$database])) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.connect.messages.database-not-found'),
                    $database
                )
            );

            return null;
        }

        $databaseConnection = $connectionInfo[$database];
        if (!in_array($databaseConnection['driver'], $this->supportedDrivers)) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.connect.messages.database-not-supported'),
                    $databaseConnection['driver']
                )
            );

            return null;
        }

        return $databaseConnection;
    }

    public function getRedBeanConnection($database = 'default')
    {
        $connectionInfo = Database::getConnectionInfo();
        $databaseConnection = $connectionInfo[$database];
        if ($databaseConnection['driver'] == 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s',
                $databaseConnection['host'],
                $databaseConnection['database']
            );

            $this->redBean->setup(
                $dsn,
                $databaseConnection['username'],
                $databaseConnection['password'],
                true
            );

            return $this->redBean;
        }

        return null;
    }
}
