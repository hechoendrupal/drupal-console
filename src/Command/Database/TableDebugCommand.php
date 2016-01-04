<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\TableDebugCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Database\ConnectTrait;

/**
 * Class TableDebugCommand
 * @package Drupal\Console\Command\Database
 */
class TableDebugCommand extends ContainerAwareCommand
{
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:table:debug')
            ->setDescription($this->trans('commands.database.table.debug.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.table.debug.arguments.database'),
                'default'
            )
            ->setHelp($this->trans('commands.database.table.debug.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $database = $input->getArgument('database');
        $databaseConnection = $this->resolveConnection($io, $database);

        $databaseService = $this->hasGetService('database');
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
