<?php

/**
 * @file
 * Contains \Drupal\Console\Command\MigrateDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\MigratePluginManager;

class MigrateSetupMigrationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:setup:migrations')
            ->setDescription($this->trans('commands.migrate.setup.migrations.description'))
            ->addOption(
                'db-type',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.migrations.options.db-type')
            )
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.migrations.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.migrations.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.migrations.options.db-user')
            )
            ->addOption(
                'db-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.migrations.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.migrations.options.db-prefix')
            )
            ->addOption(
                'db-port',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.migrations.options.db-port')
            );

        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $questionHelper = $this->getQuestionHelper();

        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception('The option can not be empty');
            }

            return $value;
        };

        // --db-type option
        $db_type = $input->getOption('db-type');
        if (!$db_type) {
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
        }
        // find current database type selected to set the proper driver id
        foreach ($databases as $db_index => $database) {
            if ($database['name'] == $db_type) {
                $db_type = $db_index;
            }
        }
        $input->setOption('db-type', $db_type);

        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.setup.migrations.questions.db-host'), '127.0.0.1'),
                $validator_required,
                false,
                '127.0.0.1'
            );
        }
        $input->setOption('db-host', $db_host);

        // --db-name option
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.setup.migrations.questions.db-name'), ''),
                $validator_required,
                false,
                null
            );
        }
        $input->setOption('db-name', $db_name);

        // --db-user option
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.setup.migrations.questions.db-user'), ''),
                $validator_required,
                false,
                null
            );
        }
        $input->setOption('db-user', $db_user);

        // --db-pass option
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $dialog->askHiddenResponse(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.setup.migrations.questions.db-pass'), ''),
                ''
            );
        }
        $input->setOption('db-pass', $db_pass);

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.setup.migrations.questions.db-prefix'), ''),
                ''
            );
        }
        $input->setOption('db-prefix', $db_prefix);

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.setup.migrations.questions.db-port'), '3306'),
                $validator_required,
                false,
                '3306'
            );
        }
        $input->setOption('db-port', $db_port);
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // Database options
        $db_type = $input->getOption('db-type');
        $db_host = $input->getOption('db-host');
        $db_name = $input->getOption('db-name');
        $db_user = $input->getOption('db-user');
        $db_pass = $input->getOption('db-pass');
        $db_prefix = $input->getOption('db-prefix');
        $db_port = $input->getOption('db-port');

        $database = array(
            'database' => $db_name,
            'username' => $db_user,
            'password' => $db_pass,
            'prefix' => $db_prefix,
            'port' => $db_port,
            'host' => $db_host,
            'namespace' => 'Drupal\Core\Database\Driver\mysql',
            'driver' => 'mysql',
        );

        // Set up the connection.
        Database::addConnectionInfo('migrate', 'default', $database);
        $connection = Database::getConnection('default', 'migrate');
        if (!$drupal_version = $this->getLegacyDrupalVersion($connection)) {
            $output->writeln(
                '[-] <error>'.
                $this->trans('commands.migrate.setup.migrations.questions.not-drupal')
                .'</error>'
            );
            return;
        }

        $database_state['key'] = 'upgrade';
        $database_state['database'] = $database;
        $database_state_key = 'migrate_upgrade_' . $drupal_version;
        \Drupal::state()->set($database_state_key, $database_state);

        $version_tag = 'Drupal ' . $drupal_version;

        $template_storage = \Drupal::service('migrate.template_storage');
        $migration_templates = $template_storage->findTemplatesByTag($version_tag);

        //print_r($migration_templates);
        $migrations = [];
        $builder = \Drupal::service('migrate.migration_builder');
        $builderManager = \Drupal::service('plugin.manager.migrate.builder');
        foreach ($migration_templates as $template_id => $template) {
            if (isset($template['builder'])) {
                // Skip for now migration requirent builder migration
                //continue;
                $variants = $builderManager
                    ->createInstance($template['builder']['plugin'], $template['builder'])
                    ->buildMigrations($template);
            } else {
                $variants = array(Migration::create($template));
            }

            /**
 * @var \Drupal\migrate\Entity\MigrationInterface[] $variants 
*/
            foreach ($variants as $variant) {
                $variant->set('template', $template_id);
            }
            $migrations = array_merge($migrations, $variants);
        }

        foreach ($migrations as $migration) {
            try {
                if ($migration->getSourcePlugin() instanceof RequirementsInterface) {
                    $migration->getSourcePlugin()->checkRequirements();
                }
                if ($migration->getDestinationPlugin() instanceof RequirementsInterface) {
                    $migration->getDestinationPlugin()->checkRequirements();
                }
                // Don't try to resave migrations that already exist.
                if (!Migration::load($migration->id())) {
                    $migration->save();
                    $migration_ids[] = $migration->id();
                }
            }
                // Migrations which are not applicable given the source and destination
                // site configurations (e.g., what modules are enabled) will be silently
                // ignored.
            catch (RequirementsException $e) {
                $output->writeln(
                    '[-] <error>'.
                    $e->getMessage()
                    .'</error>'
                );
            } catch (PluginNotFoundException $e) {
                $output->writeln(
                    '[-] <error>'.
                    $e->getMessage()
                    .'</error>'
                );
            }
        }

        if (empty($migration_ids)) {
            if (empty($migrations)) {
                $output->writeln(
                    '[-] <info>' .
                    sprintf(
                        $this->trans('commands.migrate.setup.migrations.messages.migrations-not-found'),
                        count($migrations)
                    )
                    . '</info>'
                );
            } else {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.migrate.setup.migrations.messages.migrations-already-exist'),
                        count($migrations)
                    )
                    . '</error>'
                );
            }
        } else {
            $output->writeln(
                '[-] <info>' .
                sprintf(
                    $this->trans('commands.migrate.setup.migrations.messages.migrations-created'),
                    count($migrations),
                    $version_tag
                )
                . '</info>'
            );
        }
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
}
