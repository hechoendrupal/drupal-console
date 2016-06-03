<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\TableDebugCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\ConnectTrait;

/**
 * Class TableDebugCommand
 * @package Drupal\Console\Command\Database
 */
class TableDebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:table:debug')
            ->setDescription($this->trans('commands.database.table.debug.description'))
            ->addOption(
                'database',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.table.debug.options.database'),
                'default'
            )
            ->addArgument(
                'table',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.table.debug.arguments.table'),
                null
            )
            ->setHelp($this->trans('commands.database.table.debug.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $database = $input->getOption('database');
        $table = $input->getArgument('table');

        $databaseConnection = $this->resolveConnection($io, $database);

        if ($table) {
            $redBean = $this->getRedBeanConnection($database);
            $tableInfo = $redBean->inspect($table);

            $tableHeader = [
                $this->trans('commands.database.table.debug.messages.column'),
                $this->trans('commands.database.table.debug.messages.type')
            ];
            $tableRows = [];
            foreach ($tableInfo as $column => $type) {
                $tableRows[] = [
                    'column' => $column,
                    'type' => $type
                ];
            }

            $io->table($tableHeader, $tableRows);

            return 0;
        }

        $databaseService = $this->getDrupalService('database');
        $schema = $databaseService->schema();
        $tables = $schema->findTables('%');

        $io->comment(
            sprintf(
                $this->trans('commands.database.table.debug.messages.table-show'),
                $databaseConnection['database']
            )
        );

        $io->table(
            [$this->trans('commands.database.table.debug.messages.table')],
            $tables
        );
    }
}
