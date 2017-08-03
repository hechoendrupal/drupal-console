<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\MigrationTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class MigrationTrait
 *
 * @package Drupal\Console\Command
 */
trait MigrationTrait
{
    protected $database;

    /**
     * @param bool $version_tag
     * @param bool $flatList
     *
     * @return array list of migrations
     */
    protected function getMigrations($version_tag = false, $flatList = false, $configuration = [])
    {
        //Get migration definitions by tag
        $migrations = array_filter(
            $this->pluginManagerMigration->getDefinitions(), function ($migration) use ($version_tag) {
                return !empty($migration['migration_tags']) && in_array($version_tag, $migration['migration_tags']);
            }
        );

        // Create an array to configure all migration plugins with same configuration
        $keys = array_keys($migrations);
        $migration_plugin_configuration = array_fill_keys($keys, $configuration);

        //Create all migration instances
        $all_migrations = $this->pluginManagerMigration->createInstances(array_keys($migrations), $migration_plugin_configuration);

        $migrations = [];
        foreach ($all_migrations as $migration) {
            if ($flatList) {
                $migrations[$migration->id()] = ucwords($migration->label());
            } else {
                $migrations[$migration->id()]['tags'] = implode(', ', $migration->getMigrationTags());
                $migrations[$migration->id()]['description'] = ucwords($migration->label());
            }
        }
        return  $migrations;
    }

    protected function createDatabaseStateSettings(array $database, $drupal_version)
    {
        $database_state['key'] = 'upgrade';
        $database_state['database'] = $database;
        $database_state_key = 'migrate_drupal_' . $drupal_version;

        $this->state->set($database_state_key, $database_state);
        $this->state->set('migrate.fallback_state_key', $database_state_key);
    }

     /**
     * @return mixed
     */
    protected function getDatabaseDrivers()
    {
        // Make sure the install API is available.
        include_once DRUPAL_ROOT . '/core/includes/install.inc';
        return drupal_get_database_types();
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbDriverTypeQuestion(DrupalStyle $io)
    {
        $databases = $this->getDatabaseDrivers();

        $dbType = $io->choice(
            $this->trans('commands.migrate.setup.questions.db-type'),
            array_keys($databases)
        );

        return $dbType;
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
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
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

            return null;
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

        $this->addDBConnection($io, 'upgrade', 'default', $dbType, $dbName, $dbUser, $dbPass, $dbPrefix, $dbPort, $dbHost);
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
        $database_type = $this->getDatabaseDrivers();
        $reflection = new \ReflectionClass($database_type[$dbType]);
        $install_namespace = $reflection->getNamespaceName();
        // Cut the trailing \Install from namespace.
        $namespace = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
        $this->database = [
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'prefix' => $dbPrefix,
            'port' => $dbPort,
            'host' => $dbHost,
            'namespace' => $namespace,
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

            return null;
        }
    }
}
