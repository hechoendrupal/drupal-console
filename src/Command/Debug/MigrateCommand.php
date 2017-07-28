<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\MigrateCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\MigrationTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * @DrupalCommand(
 *     extension = "migrate",
 *     extensionType = "module"
 * )
 */
class MigrateCommand extends Command
{
    use MigrationTrait;

    /**
     * @var MigrationPluginManagerInterface $pluginManagerMigration
     */
    protected $pluginManagerMigration;

    /**
     * MigrateCommand constructor.
     *
     * @param MigrationPluginManagerInterface $pluginManagerMigration
     */
    public function __construct(
        MigrationPluginManagerInterface $pluginManagerMigration
    ) {
        $this->pluginManagerMigration = $pluginManagerMigration;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('debug:migrate')
            ->setDescription($this->trans('commands.debug.migrate.description'))
            ->addArgument(
                'tag',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.migrate.arguments.tag')
            )
            ->setAliases(['mid']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $drupal_version = 'Drupal ' . $input->getArgument('tag');

        $migrations = $this->getMigrations($drupal_version);


        $tableHeader = [
          $this->trans('commands.debug.migrate.messages.id'),
          $this->trans('commands.debug.migrate.messages.description'),
          $this->trans('commands.debug.migrate.messages.tags'),
        ];

        $tableRows = [];
        if (empty($migrations)) {
            $io->error(
                sprintf(
                    $this->trans('commands.debug.migrate.messages.no-migrations'),
                    count($migrations)
                )
            );
        }
        foreach ($migrations as $migration_id => $migration) {
            $tableRows[] = [$migration_id, $migration['description'], $migration['tags']];
        }
        $io->table($tableHeader, $tableRows, 'compact');
    }
}
