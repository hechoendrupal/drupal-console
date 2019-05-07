<?php

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogPollCommand
 *
 * @package Drupal\Console\Command\Database
 */
class LogPollCommand extends DatabaseLogBase
{
    /**
     * @var
     */
    protected $duration;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getIo()->note($this->trans('commands.database.log.poll.messages.warning'));

        $this->getDefaultOptions($input);
        $this->duration = $input->getArgument('duration');

        $this->pollForEvents();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:log:poll')
            ->setDescription($this->trans('commands.database.log.poll.description'));

        $this->addDefaultLoggingOptions();

        $this->addArgument(
            'duration',
            InputArgument::OPTIONAL,
            $this->trans('commands.database.log.poll.arguments.duration'),
            '10'
        )->setAliases(['dblp']);
    }


    protected function pollForEvents()
    {
        $query = $this->makeQuery()->countQuery();
        $results = $query->execute()->fetchAssoc();
        $count = $results['expression'] - 1;//minus 1 so the newest message always prints

        $tableHeader = $this->createTableHeader();

        //Poll, force no wait on first loop
        $lastExec = time() - $this->duration;
        while (1) {
            if (time() > $lastExec + $this->duration) {
                //Print out any new db logs
                $query = $this->makeQuery($count);
                $results = $query->execute()->fetchAll();
                $count += count($results);
                $tableRows = [];
                foreach ($results as $r) {
                    $tableRows[] = $this->createTableRow($r);
                }
                if (!empty($tableRows)) {
                    $this->getIo()->table($tableHeader, $tableRows);
                }
                //update the last exec time
                $lastExec = time();
            }
        }
    }
}
