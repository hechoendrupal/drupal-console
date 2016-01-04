<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\TableDropCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Database\ConnectTrait;

/**
 * Class TableDropCommand
 * @package Drupal\Console\Command\Database
 */
class TableDropCommand extends ContainerAwareCommand
{
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:table:drop')
            ->setDescription($this->trans('commands.database.table.drop.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.table.drop.arguments.database'),
                'default'
            )
            ->setHelp($this->trans('commands.database.table.drop.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $database = $input->getArgument('database');
        $databaseConnection = $this->resolveConnection($io, $database);

        if ($io->confirm(
            sprintf(
                $this->trans('commands.database.table.drop.question.drop-tables'),
                $databaseConnection['database']
            ),
            false
        )) {
            $databaseService = $this->hasGetService('database');
            $schema = $databaseService->schema();
            $tables = $schema->findTables('%');
            $tableRows = [];

            foreach ($tables as $table) {
                if ($schema->dropTable($table)) {
                    $tableRows['success'][] = [$table];
                } else {
                    $tableRows['error'][] = [$table];
                }
            }

            $io->success(
                sprintf(
                    $this->trans('commands.database.table.drop.messages.table-drop'),
                    count($tableRows['success'])
                )
            );
        }
    }
}
