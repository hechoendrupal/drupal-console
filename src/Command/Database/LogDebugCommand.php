<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\LogDebugCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class LogDebugCommand
 *
 * @package Drupal\Console\Command\Database
 */
class LogDebugCommand extends DatabaseLogBase
{
    /**
   * @var
   */
    protected $eventId;

    /**
   * @var
   */
    protected $asc;
    /**
   * @var
   */
    protected $limit;
    /**
   * @var
   */
    protected $offset;

    /**
   * Print in yml style if true
   *
   * @var bool
   */
    protected $ymlStyle;

    /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
            ->setName('database:log:debug')
            ->setDescription($this->trans('commands.database.log.debug.description'));

        $this->addDefaultLoggingOptions();

        $this
            ->addArgument(
                'event-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.log.debug.arguments.event-id')
            )
            ->addOption(
                'asc',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.database.log.debug.options.asc')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.debug.options.limit')
            )
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.debug.options.offset'),
                0
            )
            ->addOption(
                'yml',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.database.log.debug.options.yml'),
                null
            );
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->getDefaultOptions($input);
        $this->eventId = $input->getArgument('event-id');
        $this->asc = $input->getOption('asc');
        $this->limit = $input->getOption('limit');
        $this->offset = $input->getOption('offset');
        $this->ymlStyle = $input->getOption('yml');


        if ($this->eventId) {
            return $this->getEventDetails($io);
        } else {
            return $this->getAllEvents($io);
        }
    }

    /**
   * @param $io
   * @param $eventId
   * @return bool
   */
    private function getEventDetails(DrupalStyle $io)
    {
        $dblog = $this->database
            ->query(
                'SELECT w.*, u.uid FROM {watchdog} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id',
                [':id' => $this->eventId]
            )
            ->fetchObject();

        if (!$dblog) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.log.debug.messages.not-found'),
                    $this->eventId
                )
            );
            return 1;
        }

        if ($this->ymlStyle) {
            $io->writeln(Yaml::encode($this->formatSingle($dblog)));
        } else {
            $io->table(
                $this->createTableHeader(),
                [$this->createTableRow($dblog)]
            );
        }
    }

    /**
   * @param \Drupal\Console\Style\DrupalStyle $io
   * @return bool
   */
    private function getAllEvents(DrupalStyle $io)
    {
        $query = $this->makeQuery($io);

        $result = $query->execute();

        $tableRows = [];
        foreach ($result as $dblog) {
            $tableRows[] = $this->createTableRow($dblog);
        }

        $io->table(
            $this->createTableHeader(),
            $tableRows
        );

        return true;
    }
}
