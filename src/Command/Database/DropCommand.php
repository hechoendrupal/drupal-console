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
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DropCommand
 *
 * @package Drupal\Console\Command\Database
 */
class DropCommand extends Command
{
    use ConnectTrait;

    /**
     * @var Connection
     */
    protected $database;

    /**
     * DropCommand constructor.
     *
     * @param Connection $database
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
        parent::__construct();
    }

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
            ->setHelp($this->trans('commands.database.drop.help'))
            ->setAliases(['dbd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $database = $input->getArgument('database');
        $yes = $input->getOption('yes');

        $databaseConnection = $this->resolveConnection($io, $database);

        if (!$yes) {
            if (!$io->confirm(
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

        $schema = $this->database->schema();
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

        return 0;
    }
}
