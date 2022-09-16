<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\DropCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Core\Database\Database;

/**
 * Class DropCommand
 *
 * @package Drupal\Console\Command\Database
 */
class DropCommand extends Command
{
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:drop')
            ->setDescription($this->trans('commands.database.drop.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.drop.arguments.database'),
                'default'
            )
            ->addArgument(
              'target',
              InputArgument::OPTIONAL,
              $this->trans('commands.database.drop.arguments.target'),
              'default'
            )
            ->setHelp($this->trans('commands.database.drop.help'))
            ->setAliases(['dbd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getArgument('database');
        $target = $input->getArgument('target');
        $yes = $input->getOption('yes');

        $databaseConnection = $this->resolveConnection($database, $target);

        if (!$yes) {
            if (!$this->getIo()->confirm(
                sprintf(
                    $this->trans('commands.database.drop.question.drop-tables'),
                    $databaseConnection['database']
                ),
                true
            )
            ) {
                return 1;
            }
        }

        $connection = Database::getConnection($target, $database);
        $schema = $connection->schema();
        $tables = $schema->findTables('%');
        $tableRows = [];

        foreach ($tables as $table) {
            if ($schema->dropTable($table)) {
                $tableRows['success'][] = [$table];
            } else {
                $tableRows['error'][] = [$table];
            }
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.database.drop.messages.table-drop'),
                count($tableRows['success'])
            )
        );

        return 0;
    }
}

