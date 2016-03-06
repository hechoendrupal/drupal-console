<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\DropCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Database\ConnectTrait;

/**
 * Class DropCommand
 * @package Drupal\Console\Command\Database
 */
class DropCommand extends ContainerAwareCommand
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
            ->setHelp($this->trans('commands.database.drop.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $database = $input->getArgument('database');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        $databaseConnection = $this->resolveConnection($io, $database);

        if (!$yes) {
            if (!$io->confirm(
                sprintf(
                    $this->trans('commands.database.drop.question.drop-tables'),
                    $databaseConnection['database']
                ),
                true
            )) {
                return 1;
            }
        }

        $databaseService = $this->getService('database');
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
                $this->trans('commands.database.drop.messages.table-drop'),
                count($tableRows['success'])
            )
        );
    }
}
