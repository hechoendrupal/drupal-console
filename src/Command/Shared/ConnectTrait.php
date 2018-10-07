<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ConnectTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Core\Database\Database;

trait ConnectTrait
{
    protected $supportedDrivers = ['mysql', 'pgsql'];

    public function resolveConnection($key = 'default', $target = 'default')
    {
        $connectionInfo = Database::getConnectionInfo($key);
        if (!$connectionInfo || !isset($connectionInfo[$target])) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.database.connect.messages.database-not-found'),
                    $key,
                    $target
                )
            );

            return null;
        }

        $databaseConnection = $connectionInfo[$target];
        if (!in_array($databaseConnection['driver'], $this->supportedDrivers)) {
            $this->getIo()->error(
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
