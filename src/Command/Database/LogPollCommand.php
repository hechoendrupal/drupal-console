<?php

namespace Drupal\Console\Command\Database;

use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class LogPollCommand extends Command
{

  use LogCommandTrait;

  protected $severity;
  protected $user;
  protected $type;
  protected $duration;


  protected function configure() {
    $this
      ->setName('database:log:poll')
      ->setDescription($this->trans('commands.database.log.poll.description'));

    $this->addBasicLoggingConfiguration();

    $this->addArgument(
        'duration',
        InputArgument::OPTIONAL,
        $this->trans('commands.database.log.poll.options.duration'),
        '10'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {


    $io = new DrupalStyle($input, $output);

    $io->note('For site performance it is strongly advised to have more than one core in your vm when using this script.');
    $io->note('Do not use in production environments');
    $io->note('Stop this polling mechanism before you clean/truncate the watchdog table!');

    $this->type = $input->getOption('type');
    $this->severity = $input->getOption('severity');
    $this->user = $input->getOption('user-id');
    $this->duration = $input->getArgument('duration');

    $this->pollForEvents($io);

  }

  private function makeQuery($connection, $io, $offset = null)
  {

    $query = $connection->select('watchdog', 'w');
    $query->fields(
      'w',
      [
        'wid',
        'uid',
        'severity',
        'type',
        'timestamp',
        'message',
        'variables',
      ]
    );

    if ($this->type) {
      $query->condition('type', $this->type);
    }

    $severity = RfcLogLevel::getLevels();
    if ($this->severity) {
      if (!in_array($this->severity, $severity)) {
        $io->error(
          sprintf(
            $this->trans('database.log.poll.messages.invalid-severity'),
            $this->severity
          )
        );
        return FALSE;
      }
      $query->condition('severity', array_search($this->severity, $severity));
    }

    if ($this->user) {
      $query->condition('uid', $this->user);
    }

    $query->orderBy('wid', 'ASC');

    if($offset){
      $query->range($offset,1000);
    }


    return $query;

  }

  protected function pollForEvents(DrupalStyle $io) {


    $query = $this->makeQuery($this->getDatabase(),$io);


    $result = $query->execute();
    $results = $result->fetchAll();
    $count = count($results);

    //Print the most recent message
    $tableHeader = $this->createTableHeader();
    $tableRows = [];
    if($results){
      $lastResult = array_pop($results);
      $tableRows[] = $this->createTableRow($lastResult, $this->severity);
      $io->table($tableHeader,$tableRows);
    }


    //Poll for more
    $lastExec = time();
    while (1) {
      if (time() > $lastExec + $this->duration) {
        //Print out any new db logs
        $query = $this->makeQuery($this->getDatabase(),$io,$count);
        $result = $query->execute();
        $results = $result->fetchAll();
        $count += count($results);
        $tableRows = [];
        foreach ($results as $r) {
          $tableRows[] = $this->createTableRow($r, $this->severity);
        }
        if (!empty($tableRows)) {
          $io->table($tableHeader, $tableRows);
        }
        //update the last exec time
        $lastExec = time();
      }
    }
    return TRUE;
  }



}
