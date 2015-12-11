<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\DebugCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drupal\Console\Command\ContainerAwareCommand;

class DebugCommand extends ContainerAwareCommand
{
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

        $this->addDependency('migrate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drupal_version = $input->getArgument('tag');

        $table = new Table($output);
        $table->setStyle('compact');
        $this->getAllMigrations($drupal_version, $output, $table);
    }

    protected function getAllMigrations($drupal_version, $output, $table)
    {
        $migrations = $this->getMigrations($drupal_version);

        $table->setHeaders(
            [
            $this->trans('commands.migrate.debug.messages.id'),
            $this->trans('commands.migrate.debug.messages.description'),
            $this->trans('commands.migrate.debug.messages.tags'),
            ]
        );

        $table->setStyle('compact');

        if (empty($migrations)) {
            $output->writeln(
                '[-] <error>' .
                sprintf(
                    $this->trans('commands.migrate.debug.messages.no-migrations'),
                    count($migrations)
                )
                . '</error>'
            );
        } else {
            foreach ($migrations as $migration_id => $migration) {
                $table->addRow([$migration_id, $migration['description'], $migration['tags']]);
            }
            $table->render();
        }
    }
}
