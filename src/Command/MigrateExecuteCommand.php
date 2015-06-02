<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\MigrateExecuteCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\AppConsole\Command\migrate_upgrade\MigrateExecuteMessageCapture;

class MigrateExecuteCommand extends ContainerAwareCommand
{
    protected $connection;
    protected $migration_group;

    protected function configure()
    {
        $this
          ->setName('migrate:execute')
          ->setDescription($this->trans('commands.migrate.execute.description'))
          ->addArgument('migration-ids', InputArgument::IS_ARRAY, $this->trans('commands.migrate.execute.arguments.id'))
          ->addOption('site-url', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.migrate.execute.options.site-url'))
          ->addOption('db-host', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.migrate.execute.options.db-host'))
          ->addOption('db-name', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.migrate.execute.options.db-name'))
          ->addOption('db-user', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.migrate.execute.options.db-user'))
          ->addOption('db-pass', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.migrate.execute.options.db-pass'))
          ->addOption('db-prefix', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.migrate.execute.options.db-prefix'))
          ->addOption('db-port', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.migrate.execute.options.db-port'))
          ->addOption('exclude', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.migrate.execute.options.exclude'), array());

        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception('The option can not be empty');
            }

            return $value;
        };

        $dialog = $this->getDialogHelper();

        // --site-url option
        $site_url = $input->getOption('site-url');
        if (!$site_url) {
            $site_url = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.site-url'),
                'http://www.example.com'),
              $validator_required,
              false,
              'http://www.example.com'
            );
        }
        $input->setOption('site-url', $site_url);

        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-host'), '127.0.0.1'),
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
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-name'), ''),
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
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-user'), ''),
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
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-pass'), ''), ''
            );
        }
        $input->setOption('db-pass', $db_pass);

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-prefix'), ''), ''
            );
        }
        $input->setOption('db-prefix', $db_prefix);

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-port'), '3306'),
              $validator_required,
              false,
              '3306'
            );
        }
        $input->setOption('db-port', $db_port);

        // Get migrations available
        $this->registerSourceDB($input);

        $this->getConnection($output);

        if ($this->connection->schema()->tableExists('filter_format')) {
            $this->migration_group = 'Drupal 7';
            $migrations_list = $this->getMigrations($this->migration_group);
        } elseif ($this->connection->schema()->tableExists('menu_router')) {
            $this->migration_group = 'Drupal 6';
            $migrations_list = $this->getMigrations($this->migration_group);
        } else {
            $output->writeln('[+] <error>' . $this->trans('commands.migrate.execute.questions.wrong-source') . '</error>');
            return;
        }

        if (count($migrations_list) == 0) {
            $output->writeln('[+] <error>' . $this->trans('commands.migrate.execute.messages.no-migrations') . '</error>');
            return;
        }

        // --migration-id prefix
        $migration_id = $input->getArgument('migration-ids');
        if (!$migration_id) {
            $migrations_list += array('all' => 'All');
            $migrations_ids = array();

            while (true) {
                $migration_id = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion((count($migrations_ids) == 0 ? $this->trans('commands.migrate.execute.questions.id'):$this->trans('commands.migrate.execute.questions.other-id')), 'all'),
              function ($migration) use ($migrations_list) {
                if (isset($migrations_list[$migration])) {
                    return $migration;
                } else {
                    throw new \InvalidArgumentException(
                    sprintf($this->trans('commands.migrate.execute.questions.invalid-migration-id'), $migration_id)
                  );
                }
              },
              false,
              'all',
              array_keys($migrations_list)
            );

                if (empty($migration_id) || $migration_id == 'all') {
                    if ($migration_id == 'all') {
                        $migrations_ids[] = $migration_id;
                    }
                    break;
                } else {
                    $migrations_ids[] = $migration_id;
                }
            }

            $input->setArgument('migration-ids', $migrations_ids);
        }

        // --migration-id prefix
        $exclude_ids = $input->getOption('exclude');
        if (!$exclude_ids) {
            unset($migrations_list['all']);
            while (true) {
                $exclude_id = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.exclude-id'), null),
              function ($exclude) use ($migrations_list) {
                if (empty($exclude) || isset($migrations_list[$exclude])) {
                    return $exclude;
                } else {
                    throw new \InvalidArgumentException(
                    sprintf($this->trans('commands.migrate.execute.questions.invalid-migration-id'), '@@' . $exclude)
                  );
                }
              },
              false,
              null,
              array_keys($migrations_list)
            );

                if (empty($exclude_id)) {
                    break;
                } else {
                    $exclude_ids[] = $exclude_id;
                }
            }
        }

        $input->setOption('exclude', $exclude_ids);
    }

    protected function getConnection(OutputInterface $output)
    {
        try {
            $this->connection = Database::getConnection('default', 'migrate');
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $this->trans('commands.migrate.execute.messages.destination-error') . ': ' . $e->getMessage() . '</error>');
            return;
        }

        return $this;
    }

    protected function registerSourceDB(InputInterface $input)
    {
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
          'driver' => 'mysql'
        );

        try {
            Database::addConnectionInfo('migrate', 'default', $database);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $this->trans('commands.migrate.execute.messages.source-error') . ': ' . $e->getMessage() . '</error>');
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migration_ids = $input->getArgument('migration-ids');
        $exclude_ids = $input->getArgument('exclude');

        if (!empty($exclude_ids)) {
            // Remove exclude migration from migration script
        $migration_ids = array_diff($migration_ids, $exclude_ids);
        }

      // If migrations weren't provided finish execution
      if (empty($migration_ids)) {
          return;
      }

        if (!$this->connection) {
            $this->registerSourceDB($input);
            $this->getConnection($output);
        }

        if (!in_array('all', $migration_ids)) {
            $migrations = $migration_ids;
        } else {
            $migrations = array_keys($this->getMigrations($this->migration_group));
        }

        $entity_manager = $this->getEntityManager();
        $migration_storage = $entity_manager->getStorage('migration');

        foreach ($migrations as $migration_id) {
            $output->writeln('[+] <info>' . sprintf($this->trans('commands.migrate.execute.messages.processing'),
              $migration_id) . '</info>');
            $migration = $migration_storage->load($migration_id);

            if ($migration) {
                $messages = new MigrateExecuteMessageCapture();
                $executable = new MigrateExecutable($migration, $messages);
                $migration_status = $executable->import();
                switch ($migration_status) {
                  case MigrationInterface::RESULT_COMPLETED:
                      $output->writeln('[+] <info>' . sprintf($this->trans('commands.migrate.execute.messages.imported'),
                          $migration_id) . '</info>');
                      break;
                  case MigrationInterface::RESULT_INCOMPLETE:
                      $output->writeln('[+] <info>' . sprintf($this->trans('commands.migrate.execute.messages.importing-incomplete'),
                          $migration_id) . '</info>');
                      break;
                  case MigrationInterface::RESULT_STOPPED:
                      $output->writeln('[+] <error>' . sprintf($this->trans('commands.migrate.execute.messages.import-stoped'),
                          $migration_id) . '</error>');
                      break;
                  case MigrationInterface::RESULT_FAILED:
                      $output->writeln('[+] <error>' . sprintf($this->trans('commands.migrate.execute.messages.import-fail'),
                          $migration_id) . '</error>');
                      break;
                  case MigrationInterface::RESULT_SKIPPED:
                      $output->writeln('[+] <error>' . sprintf($this->trans('commands.migrate.execute.messages.import-skipped'),
                          $migration_id) . '</error>');
                      break;
                  case MigrationInterface::RESULT_DISABLED:
                      // Skip silently if disabled.
                      break;
              }
            } else {
                $output->writeln('[+] <error>' . $this->trans('commands.migrate.execute.messages.fail-load') . '</error>');
            }
        }
    }
}
