<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\SetupCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\Database\DatabaseTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class SetupCommand extends ContainerAwareCommand
{
    use DatabaseTrait;

    protected $migrateConnection;

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
            );

        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $template_storage = \Drupal::service('migrate.template_storage');

        $this->registerMigrateDB($input, $output);
        $this->migrateConnection = $this->getDBConnection($output, 'default', 'migrate');

        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $output->writeln(
                '[-] <error>'.
                $this->trans('commands.migrate.setup.questions.not-drupal')
                .'</error>'
            );
            return;
        }

        $database_state['key'] = 'upgrade';
        $database_state['database'] = $this->getDBInfo();
        $database_state_key = 'migrate_upgrade_' . $drupal_version;

        \Drupal::state()->set($database_state_key, $database_state);

        $version_tag = 'Drupal ' . $drupal_version;

        $migration_templates = $template_storage->findTemplatesByTag($version_tag);

        $migrations = [];
        $builderManager = \Drupal::service('plugin.manager.migrate.builder');
        foreach ($migration_templates as $template_id => $template) {
            if (isset($template['builder'])) {
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
                        $this->trans('commands.migrate.setup.messages.migrations-not-found'),
                        count($migrations)
                    )
                    . '</info>'
                );
            } else {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.migrate.setup.messages.migrations-already-exist'),
                        count($migrations)
                    )
                    . '</error>'
                );
            }
        } else {
            $output->writeln(
                '[-] <info>' .
                sprintf(
                    $this->trans('commands.migrate.setup.messages.migrations-created'),
                    count($migrations),
                    $version_tag
                )
                . '</info>'
            );
        }
    }
}
