<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\DatabaseTrait.
 */

namespace Drupal\Console\Command\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

trait DatabaseTrait
{
    protected $database;
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    public function dbTypeQuestion(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        $databases = $this->getDatabaseTypes();
        $db_type = $questionHelper->ask(
            $input,
            $output,
            new ChoiceQuestion(
                $this->trans('commands.migrate.setup.migrations.questions.db-type'),
                array_combine(array_column($databases, 'name'), array_column($databases, 'name')),
                current(array_column($databases, 'name'))
            )
        );

        // find current database type selected to set the proper driver id
        foreach ($databases as $db_index => $database) {
            if ($database['name'] == $db_type) {
                $db_type = $db_index;
            }
        }

        return $db_type;
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbFileQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-file'), 'sites/default/files/.ht.sqlite'),
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            },
            false,
            'sites/default/files/.ht.sqlite'
        );
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbHostQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-host'), '127.0.0.1'),
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            },
            false,
            '127.0.0.1'
        );
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbNameQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-name'), ''),
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            },
            false,
            null
        );
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbUserQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-user'), ''),
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            },
            false,
            null
        );
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbPassQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->askHiddenResponse(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-pass'), ''),
            ''
        );
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbPrefixQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->ask(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-prefix'), ''),
            ''
        );
    }

    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function dbPortQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-port'), '3306'),
            function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception('The option can not be empty');
                }

                return $value;
            },
            false,
            '3306'
        );
    }

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

    protected function getDBInfo()
    {
        return $this->database;
    }

    protected function getDBConnection(OutputInterface $output, $target, $key)
    {
        try {
            return Database::getConnection($target, $key);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.migrate.execute.messages.destination-error').': '.$e->getMessage().'</error>');

            return;
        }
    }

    protected function registerMigrateDB(InputInterface $input, OutputInterface $output)
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
     * @param OutputInterface $output
     * @param string          $key
     * @param sting           $target
     * @param array           $info
     */
    protected function addDBConnection($output, $key, $target, $db_type, $db_name, $db_user, $db_pass, $db_prefix, $db_port, $db_host)
    {
        $databases = $this->getDatabaseTypes();

        $this->database = array(
            'database' => $db_name,
            'username' => $db_user,
            'password' => $db_pass,
            'prefix' => $db_prefix,
            'port' => $db_port,
            'host' => $db_host,
            'namespace' => $databases[$db_type]['namespace'],
            'driver' => $db_type,
        );

        try {
            return Database::addConnectionInfo($key, $target, $this->database);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.migrate.execute.messages.source-error').': '.$e->getMessage().'</error>');

            return;
        }
    }
}
