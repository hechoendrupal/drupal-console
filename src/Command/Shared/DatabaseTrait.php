<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\DatabaseTrait.
 */

namespace Drupal\Console\Command\Shared;

use Symfony\Component\Console\Input\InputInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DatabaseTrait
 * @package Drupal\Console\Command\Database
 */
trait DatabaseTrait
{
    protected $database;

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbTypeQuestion(DrupalStyle $io)
    {
        $databases = $this->getDatabaseTypes();

        $dbType = $io->choice(
            $this->trans('commands.migrate.setup.questions.db-type'),
            array_column($databases, 'name')
        );

        // find current database type selected to set the proper driver id
        foreach ($databases as $dbIndex => $database) {
            if ($database['name'] == $dbType) {
                $dbType = $dbIndex;
            }
        }

        return $dbType;
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbFileQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-file'),
            'sites/default/files/.ht.sqlite'
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbHostQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-host'),
            '127.0.0.1'
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbNameQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-name')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbUserQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-user')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbPassQuestion(DrupalStyle $io)
    {
        return $io->askHiddenEmpty(
            $this->trans('commands.migrate.execute.questions.db-pass')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbPrefixQuestion(DrupalStyle $io)
    {
        return $io->askEmpty(
            $this->trans('commands.migrate.execute.questions.db-prefix')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbPortQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-port'),
            '3306'
        );
    }

    /**
     * @return mixed
     */
    protected function getDatabaseTypes()
    {
        $drupal = $this->get('site');

        return $drupal->getDatabaseTypes();
    }

    /**
     * Determine what version of Drupal the source database contains, copied from \Drupal\migrate_upgrade\MigrationCreationTrait
     *
     * @param \Drupal\Core\Database\Connection $connection
     *
     * @return int|FALSE
     */
    protected function getLegacyDrupalVersion(Connection $connection)
    {
        // Don't assume because a table of that name exists, that it has the columns
        // we're querying. Catch exceptions and report that the source database is
        // not Drupal.

        // Druppal 5/6/7 can be detected by the schema_version in the system table.
        if ($connection->schema()->tableExists('system')) {
            try {
                $version_string = $connection->query('SELECT schema_version FROM {system} WHERE name = :module', [':module' => 'system'])
                    ->fetchField();
                if ($version_string && $version_string[0] == '1') {
                    if ((int) $version_string >= 1000) {
                        $version_string = '5';
                    } else {
                        $version_string = false;
                    }
                }
            } catch (\PDOException $e) {
                $version_string = false;
            }
        }
        // For Drupal 8 (and we're predicting beyond) the schema version is in the
        // key_value store.
        elseif ($connection->schema()->tableExists('key_value')) {
            $result = $connection->query("SELECT value FROM {key_value} WHERE collection = :system_schema  and name = :module", [':system_schema' => 'system.schema', ':module' => 'system'])->fetchField();
            $version_string = unserialize($result);
        } else {
            $version_string = false;
        }

        return $version_string ? substr($version_string, 0, 1) : false;
    }

    /**
     * @return mixed
     */
    protected function getDBInfo()
    {
        return $this->database;
    }

    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param $target
     * @param $key
     */
    protected function getDBConnection(DrupalStyle $io, $target, $key)
    {
        try {
            return Database::getConnection($target, $key);
        } catch (\Exception $e) {
            $io->error(
                sprintf(
                    '%s: %s',
                    $this->trans('commands.migrate.execute.messages.destination-error'),
                    $e->getMessage()
                )
            );

            return;
        }
    }

    /**
     * @param InputInterface $input
     * @param DrupalStyle    $io
     */
    protected function registerMigrateDB(InputInterface $input, DrupalStyle $io)
    {
        $dbType = $input->getOption('db-type');
        $dbHost = $input->getOption('db-host');
        $dbName = $input->getOption('db-name');
        $dbUser = $input->getOption('db-user');
        $dbPass = $input->getOption('db-pass');
        $dbPrefix = $input->getOption('db-prefix');
        $dbPort = $input->getOption('db-port');

        $this->addDBConnection($io, 'migrate', 'default', $dbType, $dbName, $dbUser, $dbPass, $dbPrefix, $dbPort, $dbHost);

        // Set container to static Drupal method to get services available
        // Issue: https://github.com/hechoendrupal/DrupalConsole/issues/1129
        \Drupal::setContainer($this->getApplication()->getContainer());
    }


    /**
     * @param DrupalStyle $io
     * @param $key
     * @param $target
     * @param $dbType
     * @param $dbName
     * @param $dbUser
     * @param $dbPass
     * @param $dbPrefix
     * @param $dbPort
     * @param $dbHost
     */
    protected function addDBConnection(DrupalStyle $io, $key, $target, $dbType, $dbName, $dbUser, $dbPass, $dbPrefix, $dbPort, $dbHost)
    {
        $databases = $this->getDatabaseTypes();

        $this->database = [
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'prefix' => $dbPrefix,
            'port' => $dbPort,
            'host' => $dbHost,
            'namespace' => $databases[$dbType]['namespace'],
            'driver' => $dbType,
        ];

        try {
            return Database::addConnectionInfo($key, $target, $this->database);
        } catch (\Exception $e) {
            $io->error(
                sprintf(
                    '%s: %s',
                    $this->trans('commands.migrate.execute.messages.source-error'),
                    $e->getMessage()
                )
            );

            return;
        }
    }
}
