<?php

namespace Drupal\Console\Command\Database;

use Drupal\Console\Core\Style\DrupalStyle;
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
   *
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
        );
    }

    /**
   * @param \Symfony\Component\Console\Input\InputInterface   $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $io->note($this->trans('commands.database.log.poll.messages.warning'));

        $this->getDefaultOptions($input);
        $this->duration = $input->getArgument('duration');

        $this->pollForEvents($io);
    }

    /**
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   */
    protected function pollForEvents(DrupalStyle $io)
    {
        $query = $this->makeQuery($io)->countQuery();
        $results = $query->execute()->fetchAssoc();
        $count = $results['expression'] - 1;//minus 1 so the newest message always prints

        $tableHeader = $this->createTableHeader();

        //Poll, force no wait on first loop
        $lastExec = time() - $this->duration;
        while (1) {
            if (time() > $lastExec + $this->duration) {
                //Print out any new db logs
                $query = $this->makeQuery($io, $count);
                $results = $query->execute()->fetchAll();
                $count += count($results);
                $tableRows = [];
                foreach ($results as $r) {
                    $tableRows[] = $this->createTableRow($r);
                }
                if (!empty($tableRows)) {
                    $io->table($tableHeader, $tableRows);
                }
                //update the last exec time
                $lastExec = time();
            }
        }
    }
}
