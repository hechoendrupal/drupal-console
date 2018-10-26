<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\DatabaseTableCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use RedBeanPHP\R;
use Drupal\Core\Database\Connection;
use Drupal\Console\Command\Shared\ConnectTrait;

/**
 * Class DatabaseTableCommand
 *
 * @package Drupal\Console\Command\Database
 */
class DatabaseTableCommand extends Command
{
    use ConnectTrait;

    /**
     * @var Connection
     */
    protected $database;

    /**
     * DatabaseTableCommand constructor.
     *
     * @param Connection $database
     */
    public function __construct(
        Connection $database
    ) {
        $this->database = $database;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:database:table')
            ->setDescription($this->trans('commands.debug.database.table.description'))
            ->addOption(
                'database',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.database.table.options.database'),
                'default'
            )
            ->addArgument(
                'table',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.database.table.arguments.table'),
                null
            )
            ->setHelp($this->trans('commands.debug.database.table.help'))
            ->setAliases(['ddt']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getOption('database');
        $table = $input->getArgument('table');
        $databaseConnection = $this->resolveConnection($database);
        if ($table) {
            $result = $this->database
                ->query('DESCRIBE '. $table .';')
                ->fetchAll();
            if (!$result) {
                throw new \Exception(
                    sprintf(
                        $this->trans('commands.debug.database.table.messages.no-connection'),
                        $database
                    )
                );
            }

            $tableHeader = [
                $this->trans('commands.debug.database.table.messages.column'),
                $this->trans('commands.debug.database.table.messages.type')
            ];
            $tableRows = [];
            foreach ($result as $record) {
                $column = json_decode(json_encode($record), true);
                $tableRows[] = [
                    'column' => $column['Field'],
                    'type' => $column['Type'],
                ];
            }

            $this->getIo()->table($tableHeader, $tableRows);

            return 0;
        }

        $schema = $this->database->schema();
        $tables = $schema->findTables('%');

        $this->getIo()->comment(
            sprintf(
                $this->trans('commands.debug.database.table.messages.table-show'),
                $databaseConnection['database']
            )
        );

        $this->getIo()->table(
            [$this->trans('commands.debug.database.table.messages.table')],
            $tables
        );

        return 0;
    }
}
