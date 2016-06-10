<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\SetupCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\DatabaseTrait;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class SetupCommand extends Command
{
    use ContainerAwareCommandTrait;
    use DatabaseTrait;

    protected function configure()
    {
        $this
            ->setName('migrate:setup')
            ->setDescription($this->trans('commands.migrate.setup.description'))
            ->addOption(
                'db-type',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-type')
            )
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-user')
            )
            ->addOption(
                'db-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.db-prefix')
            )
            ->addOption(
                'db-port',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-port')
            )
            ->addOption(
                'files-directory',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.files-directory')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

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

         // --files-directory
        $files_directory = $input->getOption('files-directory');
        if (!$files_directory) {
            $files_directory = $io->ask(
                $this->trans('commands.migrate.setup.questions.files-directory'),
                ''
            );
            $input->setOption('files-directory', $files_directory);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $template_storage = $this->getDrupalService('migrate.template_storage');
        $source_base_path = $input->getOption('files-directory');

        $this->registerMigrateDB($input, $output);
        $migrateConnection = $this->getDBConnection($io, 'default', 'migrate');

        if (!$drupal_version = $this->getLegacyDrupalVersion($migrateConnection)) {
            $io->error($this->trans('commands.migrate.setup.questions.not-drupal'));

            return 1;
        }

        $database_state['key'] = 'upgrade';
        $database_state['database'] = $this->getDBInfo();
        $database_state_key = 'migrate_upgrade_' . $drupal_version;

        \Drupal::state()->set($database_state_key, $database_state);

        $version_tag = 'Drupal ' . $drupal_version;

        $migration_templates = $template_storage->findTemplatesByTag($version_tag);

        $builderManager = $this->getDrupalService('migrate.migration_builder');
        foreach ($migration_templates as $id => $template) {
            $migration_templates[$id]['source']['database_state_key'] = $database_state_key;
            // Configure file migrations so they can find the files.
            if ($template['destination']['plugin'] == 'entity:file') {
                if ($source_base_path) {
                    // Make sure we have a single trailing slash.
                    $source_base_path = rtrim($source_base_path, '/') . '/';
                    $migration_templates[$id]['destination']['source_base_path'] = $source_base_path;
                }
            }
        }

        // Let the builder service create our migration configuration entities from
        // the templates, expanding them to multiple entities where necessary.
        /**
         * @var \Drupal\migrate\MigrationBuilder $builder
         */
        $migrations = $builderManager->createMigrations($migration_templates);
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
                $io->error($e->getMessage());
            } catch (PluginNotFoundException $e) {
                $io->error($e->getMessage());
            }
        }

        if (empty($migration_ids)) {
            if (empty($migrations)) {
                $io->info(
                    sprintf(
                        $this->trans('commands.migrate.setup.messages.migrations-not-found'),
                        count($migrations)
                    )
                );
            } else {
                $io->error(
                    sprintf(
                        $this->trans('commands.migrate.setup.messages.migrations-already-exist'),
                        count($migrations)
                    )
                );
            }
        } else {
            $io->info(
                sprintf(
                    $this->trans('commands.migrate.setup.messages.migrations-created'),
                    count($migrations),
                    $version_tag
                )
            );
        }
    }
}
