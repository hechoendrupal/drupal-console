<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\DebugCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\MigrationTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * @DrupalCommand(
 *     extension = "migrate",
 *     extensionType = "module"
 * )
 */

class DebugCommand extends Command
{
    use MigrationTrait;
    use CommandTrait;

    /**
     * @var MigrationPluginManagerInterface $pluginManagerMigration
     */
    protected $pluginManagerMigration;

    /**
     * DebugCommand constructor.
     *
     * @param MigrationPluginManagerInterface $pluginManagerMigration
     */
    public function __construct(MigrationPluginManagerInterface $pluginManagerMigration)
    {
        $this->pluginManagerMigration = $pluginManagerMigration;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('migrate:debug')
            ->setDescription($this->trans('commands.migrate.debug.description'))
            ->addArgument(
                'tag',
                InputArgument::OPTIONAL,
                $this->trans('commands.migrate.debug.arguments.tag')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $drupal_version = 'Drupal ' . $input->getArgument('tag');
        
        $migrations = $this->getMigrations($drupal_version);
        

        $tableHeader = [
          $this->trans('commands.migrate.debug.messages.id'),
          $this->trans('commands.migrate.debug.messages.description'),
          $this->trans('commands.migrate.debug.messages.tags'),
        ];

        $tableRows = [];
        if (empty($migrations)) {
            $io->error(
                sprintf(
                    $this->trans('commands.migrate.debug.messages.no-migrations'),
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
