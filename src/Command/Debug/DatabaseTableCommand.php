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
use Symfony\Component\Console\Command\Command;
use RedBeanPHP\R;
use Drupal\Core\Database\Connection;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Command\Shared\ConnectTrait;

/**
 * Class DatabaseTableCommand
 *
 * @package Drupal\Console\Command\Database
 */
class DatabaseTableCommand extends Command
{
    use CommandTrait;
    use ConnectTrait;

    /**
     * @var Connection
     */
    protected $database;

    /**
     * @var R
     */
    protected $redBean;

    /**
     * DatabaseTableCommand constructor.
     *
     * @param R          $redBean
     * @param Connection $database
     */
    public function __construct(
        R $redBean,
        Connection $database
    ) {
        $this->redBean = $redBean;
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
        $io = new DrupalStyle($input, $output);
        $database = $input->getOption('database');
        $table = $input->getArgument('table');

        $databaseConnection = $this->resolveConnection($io, $database);

        if ($table) {
            $this->redBean = $this->getRedBeanConnection($database);
            $tableInfo = $this->redBean->inspect($table);

            $tableHeader = [
                $this->trans('commands.debug.database.table.messages.column'),
                $this->trans('commands.debug.database.table.messages.type')
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

        $schema = $this->database->schema();
        $tables = $schema->findTables('%');

        $io->comment(
            sprintf(
                $this->trans('commands.debug.database.table.messages.table-show'),
                $databaseConnection['database']
            )
        );

        $io->table(
            [$this->trans('commands.debug.database.table.messages.table')],
            $tables
        );

        return 0;
    }
}
