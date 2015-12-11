<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\DatabaseTrait.
 */

namespace Drupal\Console\Command\Database;

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
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbTypeQuestion(DrupalStyle $output)
    {
        $databases = $this->getDatabaseTypes();
        $dbType = $output->ask(
            $this->trans('commands.migrate.setup.migrations.questions.db-type'),
            array_combine(array_column($databases, 'name'), array_column($databases, 'name')),
            current(array_column($databases, 'name'))
        );

        // find current database type selected to set the proper driver id
        foreach ($databases as $db_index => $database) {
            if ($database['name'] == $dbType) {
                $dbType = $db_index;
            }
        }

        return $dbType;
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbFileQuestion(DrupalStyle $output)
    {
        return $output->ask(
            $this->trans('commands.migrate.execute.questions.db-file'),
            'sites/default/files/.ht.sqlite',
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            }
        );
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbHostQuestion(DrupalStyle $output)
    {
        return $output->ask(
            $this->trans('commands.migrate.execute.questions.db-host'),
            '127.0.0.1',
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            }
        );
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbNameQuestion(DrupalStyle $output)
    {
        return $output->ask(
            $this->trans('commands.migrate.execute.questions.db-name'),
            null,
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            }
        );
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbUserQuestion(DrupalStyle $output)
    {
        return $output->ask(
            $this->trans('commands.migrate.execute.questions.db-user'),
            null,
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            }
        );
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbPassQuestion(DrupalStyle $output)
    {
        return $output->askHidden(
            $this->trans('commands.migrate.execute.questions.db-pass')
        );
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbPrefixQuestion(DrupalStyle $output)
    {
        return $output->ask(
            $this->trans('commands.migrate.execute.questions.db-prefix')
        );
    }

    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function dbPortQuestion(DrupalStyle $output)
    {
        return $output->ask(
            $this->trans('commands.migrate.execute.questions.db-port'),
            '3306',
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            }
        );
    }

    /**
     * @return mixed
     */
    protected function getDatabaseTypes()
    {
        $drupal = $this->getDrupalHelper();

        $databases = $drupal->getDatabaseTypes();

        return $databases;
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
     * @param \Drupal\Console\Style\DrupalStyle $output
     * @param $target
     * @param $key
     */
    protected function getDBConnection(DrupalStyle $output, $target, $key)
    {
        try {
            return Database::getConnection($target, $key);
        } catch (\Exception $e) {
            $output->error(
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
     * @param DrupalStyle    $output
     */
    protected function registerMigrateDB(InputInterface $input, DrupalStyle $output)
    {
        $db_type = $input->getOption('db-type');
        $db_host = $input->getOption('db-host');
        $db_name = $input->getOption('db-name');
        $db_user = $input->getOption('db-user');
        $db_pass = $input->getOption('db-pass');
        $db_prefix = $input->getOption('db-prefix');
        $db_port = $input->getOption('db-port');

        $this->addDBConnection($output, 'migrate', 'default', $db_type, $db_name, $db_user, $db_pass, $db_prefix, $db_port, $db_host);

        // Set static container to static Drupal method to get services available Issue: https://github.com/hechoendrupal/DrupalConsole/issues/1129
        \Drupal::setContainer($this->getContainer());
    }


    /**
     * @param DrupalStyle $output
     * @param $key
     * @param $target
     * @param $db_type
     * @param $db_name
     * @param $db_user
     * @param $db_pass
     * @param $db_prefix
     * @param $db_port
     * @param $db_host
     */
    protected function addDBConnection(DrupalStyle $output, $key, $target, $db_type, $db_name, $db_user, $db_pass, $db_prefix, $db_port, $db_host)
    {
        $databases = $this->getDatabaseTypes();

        $this->database = [
            'database' => $db_name,
            'username' => $db_user,
            'password' => $db_pass,
            'prefix' => $db_prefix,
            'port' => $db_port,
            'host' => $db_host,
            'namespace' => $databases[$db_type]['namespace'],
            'driver' => $db_type,
        ];

        try {
            return Database::addConnectionInfo($key, $target, $this->database);
        } catch (\Exception $e) {
            $output->error(
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
