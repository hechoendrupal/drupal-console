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

class SiteInstallCommand extends Command
{
    protected $connection;
    protected $migration_group;

    protected function configure()
    {
        $this
            ->setName('site:install')
            ->setDescription($this->trans('commands.site.install.description'))
            ->addArgument('profile', InputArgument::REQUIRED, $this->trans('commands.site.install.arguments.profile'))
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-user')
            )
            ->addOption(
                'db-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-prefix')
            )
            ->addOption(
                'db-port',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-port')
            );

        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {

        print 'aqui2';
        exit();

        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception('The option can not be empty');
            }

            return $value;
        };

        $dialog = $this->getDialogHelper();

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
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-pass'), ''),
                ''
            );
        }
        $input->setOption('db-pass', $db_pass);

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-prefix'), ''),
                ''
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
        //$this->registerDB($input);

        //$this->getConnection($output);
    }

    protected function getConnection(OutputInterface $output)
    {
        try {
            $this->connection = Database::getConnection('default', 'install');
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.migrate.execute.messages.destination-error').': '.$e->getMessage().'</error>');

            return;
        }

        return $this;
    }

    protected function registerDB(InputInterface $input, OutputInterface $output)
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
          'driver' => 'mysql',
        );

        try {
            Database::addConnectionInfo('install', 'default', $database);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.migrate.execute.messages.source-error').': '.$e->getMessage().'</error>');

            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        print 'aqui';
        exit();

        if (!$this->connection) {
            $this->registerSourceDB($input);
            $this->getConnection($output);
        }

        $entity_manager = $this->getEntityManager();
        $migration_storage = $entity_manager->getStorage('migration');
        if (count($migrations) == 0) {
            $output->writeln('[+] <error>'.$this->trans('commands.migrate.execute.messages.no-migrations').'</error>');
            return;
        }
        foreach ($migrations as $migration_id) {
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.migrate.execute.messages.processing'),
                    $migration_id
                ).'</info>'
            );
            $migration = $migration_storage->load($migration_id);

            if ($migration) {
                $messages = new MigrateExecuteMessageCapture();
                $executable = new MigrateExecutable($migration, $messages);
                $migration_status = $executable->import();
                switch ($migration_status) {
                case MigrationInterface::RESULT_COMPLETED:
                    $output->writeln(
                        '[+] <info>'.sprintf(
                            $this->trans('commands.migrate.execute.messages.imported'),
                            $migration_id
                        ).'</info>'
                    );
                    break;
                case MigrationInterface::RESULT_INCOMPLETE:
                    $output->writeln(
                        '[+] <info>'.sprintf(
                            $this->trans('commands.migrate.execute.messages.importing-incomplete'),
                            $migration_id
                        ).'</info>'
                    );
                    break;
                case MigrationInterface::RESULT_STOPPED:
                    $output->writeln(
                        '[+] <error>'.sprintf(
                            $this->trans('commands.migrate.execute.messages.import-stopped'),
                            $migration_id
                        ).'</error>'
                    );
                    break;
                case MigrationInterface::RESULT_FAILED:
                    $output->writeln(
                        '[+] <error>'.sprintf(
                            $this->trans('commands.migrate.execute.messages.import-fail'),
                            $migration_id
                        ).'</error>'
                    );
                    break;
                case MigrationInterface::RESULT_SKIPPED:
                    $output->writeln(
                        '[+] <error>'.sprintf(
                            $this->trans('commands.migrate.execute.messages.import-skipped'),
                            $migration_id
                        ).'</error>'
                    );
                    break;
                case MigrationInterface::RESULT_DISABLED:
                    // Skip silently if disabled.
                    break;
                }
            } else {
                $output->writeln('[+] <error>'.$this->trans('commands.migrate.execute.messages.fail-load').'</error>');
            }
        }
    }
}
