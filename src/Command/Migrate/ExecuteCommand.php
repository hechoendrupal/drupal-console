<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\ExecuteCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\Console\Utils\MigrateExecuteMessageCapture;
use Drupal\Console\Command\Database\DatabaseTrait;
use Drupal\Console\Style\DrupalStyle;

class ExecuteCommand extends ContainerAwareCommand
{
    use DatabaseTrait;

    protected $migrateConnection;

    protected function configure()
    {
        $this
            ->setName('migrate:execute')
            ->setDescription($this->trans('commands.migrate.execute.description'))
            ->addArgument('migration-ids', InputArgument::IS_ARRAY, $this->trans('commands.migrate.execute.arguments.id'))
            ->addOption(
                'site-url',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.site-url')
            )
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
            )
            ->addOption(
                'exclude',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.migrate.execute.options.exclude'),
                array()
            );

        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception('The option can not be empty');
            }

            return $value;
        };

        // --site-url option
        $site_url = $input->getOption('site-url');
        if (!$site_url) {
            $site_url = $output->ask(
                $this->trans('commands.migrate.execute.questions.site-url'),
                'http://www.example.com',
                $validator_required
            );
            $input->setOption('site-url', $site_url);
        }

        // --db-type option
        $db_type = $input->getOption('db-type');
        if (!$db_type) {
            $db_type = $this->dbTypeQuestion($output);
            $input->setOption('db-type', $db_type);
        }

        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $this->dbHostQuestion($output);
            $input->setOption('db-host', $db_host);
        }

        // --db-name option
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $this->dbNameQuestion($output);
            $input->setOption('db-name', $db_name);
        }

        // --db-user option
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $this->dbUserQuestion($output);
            $input->setOption('db-user', $db_user);
        }

        // --db-pass option
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $this->dbPassQuestion($output);
            $input->setOption('db-pass', $db_pass);
        }

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $this->dbPrefixQuestion($output);
            $input->setOption('db-prefix', $db_prefix);
        }

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $this->dbPortQuestion($output);
            $input->setOption('db-port', $db_port);
        }

        $this->registerMigrateDB($input, $output);
        $this->migrateConnection = $this->getDBConnection($output, 'default', 'migrate');

        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $output->writeln(
                '[-] <error>'.
                $this->trans('commands.migrate.setup.migrations.questions.not-drupal')
                .'</error>'
            );
            return;
        }

        $version_tag = 'Drupal ' . $drupal_version;
        // Get migrations available
        $migrations_list = $this->getMigrations($version_tag);

        // --migration-id prefix
        $migration_id = $input->getArgument('migration-ids');
        if (!$migration_id) {
            $migrations_list += array('all' => 'All');
            $migrations_ids = [];

            while (true) {
                $migration_id = $output->choice(
                    $this->trans('commands.migrate.execute.questions.id'),
                    $migrations_list,
                    'all'
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
                $exclude_id = $output->choiceNoList(
                    $this->trans('commands.migrate.execute.questions.exclude-id'),
                    array_keys($migrations_list)
                );

                if (empty($exclude_id)) {
                    break;
                } else {
                    unset($migrations_list[$exclude_id]);
                    $exclude_ids[] = $exclude_id;
                }
            }
            $input->setOption('exclude', $exclude_ids);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migration_ids = $input->getArgument('migration-ids');
        $exclude_ids = $input->getOption('exclude');
        if (!empty($exclude_ids)) {
            // Remove exclude migration from migration script
            $migration_ids = array_diff($migration_ids, $exclude_ids);
        }

        // If migrations weren't provided finish execution
        if (empty($migration_ids)) {
            return;
        }

        if (!$this->migrateConnection) {
            $this->registerMigrateDB($input, $output);
            $this->migrateConnection = $this->getDBConnection($output, 'default', 'migrate');
        }

        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $output->writeln(
                '[-] <error>'.
                $this->trans('commands.migrate.setup.migrations.questions.not-drupal')
                .'</error>'
            );
            return;
        }

        $version_tag = 'Drupal ' . $drupal_version;

        if (!in_array('all', $migration_ids)) {
            $migrations = $migration_ids;
        } else {
            $migrations = array_keys($this->getMigrations($version_tag));
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
