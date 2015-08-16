<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\MigrateDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:debug')
            ->setDescription($this->trans('commands.migrate.debug.description'))
            ->addArgument(
                'drupal-version',
                InputArgument::OPTIONAL,
                $this->trans('commands.migrate.debug.arguments.drupal-version')
            );

        $this->addDependency('migrate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drupal_version = $input->getArgument('drupal-version');

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);
        $this->getAllMigrations($drupal_version, $output, $table);
    }

    protected function getAllMigrations($drupal_version, $output, $table)
    {
        $migrations = $this->getMigrations($drupal_version);

        $table->setHeaders(
            [
            $this->trans('commands.migrate.debug.messages.id'),
            $this->trans('commands.migrate.debug.messages.description'),
            $this->trans('commands.migrate.debug.messages.version'),
            ]
        );

        $table->setlayout($table::LAYOUT_COMPACT);

        foreach ($migrations as $migration_id => $migration) {
            $table->addRow([$migration_id, $migration['description'], $migration['version']]);
        }
        $table->render($output);
    }
}
