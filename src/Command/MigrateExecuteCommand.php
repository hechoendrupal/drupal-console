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
          ->addArgument('migration-id', InputArgument::REQUIRED, $this->trans('commands.migrate.execute.arguments.id'))
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
            $this->trans('commands.migrate.execute.options.db-port'));

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


        // --migration-id prefix
        $migration_id = $input->getArgument('migration-id');
        if (!$migration_id) {
            $this->registerSourceDB($input);

            $this->getConnection($output);

            if ($this->connection->schema()->tableExists('filter_format')) {
                $this->migration_group = 'Drupal 7';
                $migrations = $this->getMigrations($this->migration_group);
            } elseif ($this->connection->schema()->tableExists('menu_router')) {
                $this->migration_group = 'Drupal 6';
                $migrations = $this->getMigrations($this->migration_group);
            } else {
                $output->writeln('[+] <error>' . $this->trans('commands.migrate.execute.questions.wrong-source') . '</error>');
                return;
            }

            $migrations += array('all' => 'All');

            $migration_id = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.execute.questions.id'), 'all'),
              function ($migration_id) use ($migrations) {
                  if ($migrations[$migration_id]) {
                      return $migration_id;
                  } else {
                      throw new \InvalidArgumentException(
                        sprintf($this->trans('commands.migrate.execute.questions.invalid-migration-id'), $migration_id)
                      );
                  }
              },
              false,
              'all',
              array_keys($migrations)
            );

            $input->setArgument('migration-id', $migration_id);
        }
    }

    protected function getConnection(OutputInterface $output)
    {
        try {
            $this->connection = Database::getConnection('default', 'migrate');
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
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

        Database::addConnectionInfo('migrate', 'default', $database);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migration_id = $input->getArgument('migration-id');

        if (!$this->connection) {
            $this->registerSourceDB($input);
            $this->getConnection($output);
        }

        if ($migration_id != 'all') {
            $migrations = array($migration_id);
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
