<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\SetupCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\State\StateInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Command\Shared\DatabaseTrait;
use Drupal\Console\Command\Shared\MigrationTrait;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * @DrupalCommand(
 *     extension = "migrate",
 *     extensionType = "module"
 * )
 */
class SetupCommand extends ContainerAwareCommand
{
    use DatabaseTrait;
    use MigrationTrait;

    /**
     * @var StateInterface $state
     */
    protected $state;

    /**
     * @var MigrationPluginManagerInterface $pluginManagerMigration
     */
    protected $pluginManagerMigration;

    /**
     * SetupCommand constructor.
     *
     * @param StateInterface $pluginManagerMigration
     */
    public function __construct(
        StateInterface $state,
        MigrationPluginManagerInterface $pluginManagerMigration
    ) {
        $this->state = $state;
        $this->pluginManagerMigration = $pluginManagerMigration;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('migrate:setup')
            ->setDescription($this->trans('commands.migrate.setup.description'))
            ->addOption(
                'db-type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-type')
            )
            ->addOption(
                'db-host',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-host')
            )
            ->addOption(
                'db-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-name')
            )
            ->addOption(
                'db-user',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-user')
            )
            ->addOption(
                'db-pass',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.db-prefix')
            )
            ->addOption(
                'db-port',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.setup.options.db-port')
            )
            ->addOption(
                'source-base_path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.setup.options.source-base-path')
            )->setAliases(['mis']);
        ;
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
            $db_type = $this->dbDriverTypeQuestion($io);
            $input->setOption('db-type', $db_type);
        }

        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $this->dbHostQuestion($io);
            $input->setOption('db-host', $db_host);
        }

        // --db-name option
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $this->dbNameQuestion($io);
            $input->setOption('db-name', $db_name);
        }

        // --db-user option
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $this->dbUserQuestion($io);
            $input->setOption('db-user', $db_user);
        }

        // --db-pass option
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $this->dbPassQuestion($io);
            $input->setOption('db-pass', $db_pass);
        }

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $this->dbPrefixQuestion($io);
            $input->setOption('db-prefix', $db_prefix);
        }

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $this->dbPortQuestion($io);
            $input->setOption('db-port', $db_port);
        }

        // --source-base_path
        $sourceBasepath = $input->getOption('source-base_path');
        if (!$sourceBasepath) {
            $sourceBasepath = $io->ask(
                $this->trans('commands.migrate.setup.questions.source-base-path'),
                ''
            );
            $input->setOption('source-base_path', $sourceBasepath);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $sourceBasepath = $input->getOption('source-base_path');
        $configuration['source']['constants']['source_base_path'] = rtrim($sourceBasepath, '/') . '/';

        $this->registerMigrateDB($input, $io);
        $this->migrateConnection = $this->getDBConnection($io, 'default', 'upgrade');

        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $io->error($this->trans('commands.migrate.setup.migrations.questions.not-drupal'));
            return 1;
        }
        
        $database = $this->getDBInfo();
        $version_tag = 'Drupal ' . $drupal_version;
        
        $this->createDatabaseStateSettings($database, $drupal_version);
        
        $migrations  = $this->getMigrations($version_tag, false, $configuration);
        
        if ($migrations) {
            $io->info(
                sprintf(
                    $this->trans('commands.migrate.setup.messages.migrations-created'),
                    count($migrations),
                    $version_tag
                )
            );
        }

        return 0;
    }
}
