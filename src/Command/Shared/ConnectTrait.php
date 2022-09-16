<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ConnectTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Core\Database\Database;

trait ConnectTrait
{
    protected $supportedDrivers = ['mysql', 'pgsql', 'sqlite'];

    public function resolveConnection($key = 'default', $target = 'default')
    {
        $connectionInfo = Database::getConnectionInfo($key);
        if (empty($connectionInfo[$target])) {
            throw new \Exception(sprintf(
                $this->trans('commands.database.connect.messages.database-not-found'),
                $key,
                $target
            ));
        }
        else if (!in_array($connectionInfo[$target]['driver'], $this->supportedDrivers)) {
            throw new \Exception(sprintf(
                $this->trans('commands.database.connect.messages.database-not-supported'),
                $connectionInfo[$target]['driver']
            ));
        }

        return $connectionInfo[$target];
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

    public function getConnectionString($databaseConnection) {
        return sprintf(
          '%s -A --database=%s --user=%s --password=%s --host=%s --port=%s',
          $databaseConnection['driver'],
          $databaseConnection['database'],
          $databaseConnection['username'],
          $databaseConnection['password'],
          $databaseConnection['host'],
          $databaseConnection['port']
        );
    }

    public function escapeConnection($databaseConnection) {
        $settings = [
          'driver', 'database', 'username', 'password', 'host', 'port'
        ];

        foreach ($settings as $setting) {
            $databaseConnection[$setting] = $databaseConnection[$setting];
        }

        return $databaseConnection;
    }
}