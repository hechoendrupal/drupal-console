<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\MigrationTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class MigrationTrait
 * @package Drupal\Console\Command
 */
trait MigrationTrait
{
    /**
     * @param bool $version_tag
     * @param bool $flatList
     *
     * @return array list of migrations
     */
    protected function getMigrations($version_tag = false, $flatList = false)
    {
        $plugin_manager = $this->getDrupalService('plugin.manager.migration');
        $all_migrations = $plugin_manager->createInstancesByTag($version_tag);
 
        $migrations = array();
        foreach ($all_migrations as $migration) {
            if ($flatList) {
                $migrations[$migration->id()] = ucwords($migration->label());
            } else {
                $migrations[$migration->id()]['tags'] = implode(', ', $migration->migration_tags);
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

        $state_service = $this->getDrupalService('state');
        $state_service->set($database_state_key, $database_state);
        $state_service->set('migrate.fallback_state_key', $database_state_key);
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
}
