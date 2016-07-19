<?php

namespace Drupal\dc_console_tail\Command\Database;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogPollCommand extends Command {
  use ContainerAwareCommandTrait;

  protected $severity;
  protected $user;
  protected $type;
  protected $duration;

  protected function configure() {
    $this
      ->setName('database:log:poll')
      ->setDescription($this->trans('commands.database.log.poll.description'))
      ->addArgument(
        'duration',
        InputArgument::OPTIONAL,
        $this->trans('commands.database.log.poll.options.duration'),
        '10'
      )
      ->addOption(
        'type',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.database.log.poll.options.type')
      )
      ->addOption(
        'severity',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.database.log.poll.options.severity')
      )
      ->addOption(
        'user-id',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.database.log.poll.options.user-id')
      );;
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
    $connection = $this->getDrupalService('database');
    $dateFormatter = $this->getDrupalService('date.formatter');
    $userStorage = $this->getDrupalService('entity_type.manager')
      ->getStorage('user');


    $query = $this->makeQuery($connection,$io);


    $result = $query->execute();
    $results = $result->fetchAll();
    $count = count($results);

    //Print the most recent message
    $tableHeader = $this->createTableHeader();
    $tableRows = [];
    if($results){
      $lastResult = array_pop($results);
      $tableRows[] = $this->createTableRows($lastResult, $userStorage, $dateFormatter, $this->severity);
      $io->table($tableHeader,$tableRows);
    }


    //Poll for more
    $lastExec = time();
    while (1) {
      if (time() > $lastExec + $this->duration) {
        //Print out any new db logs
        $query = $this->makeQuery($connection,$io,$count);
        $result = $query->execute();
        $results = $result->fetchAll();
        $count += count($results);
        $tableRows = [];
        foreach ($results as $r) {
          $tableRows[] = $this->createTableRows($r, $userStorage, $dateFormatter, $this->severity);
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


  private function createTableHeader() {
    return [
      $this->trans('commands.database.log.debug.messages.event-id'),
      $this->trans('commands.database.log.debug.messages.type'),
      $this->trans('commands.database.log.debug.messages.date'),
      $this->trans('commands.database.log.debug.messages.message'),
      $this->trans('commands.database.log.debug.messages.user'),
      $this->trans('commands.database.log.debug.messages.severity'),
    ];
  }

  private function createTableRows($dblog, $userStorage, $dateFormatter, $severity) {
    $user = $userStorage->load($dblog->uid);

    return [
      $dblog->wid,
      $dblog->type,
      $dateFormatter->format($dblog->timestamp, 'short'),
      Unicode::truncate(Html::decodeEntities(strip_tags($this->formatMessage($dblog))), 500, TRUE, TRUE),
      $user->getUsername() . ' (' . $user->id() . ')',
      $severity[$dblog->severity]
    ];
  }

  /**
   * Formats a database log message.
   *
   * @param $event
   *   The record from the watchdog table. The object properties are: wid, uid,
   *   severity, type, timestamp, message, variables, link, name.
   *
   * @return string|false
   *   The formatted log message or FALSE if the message or variables properties
   *   are not set.
   */
  public function formatMessage($event) {
    $stringTranslation = $this->getDrupalService('string_translation');
    $message = FALSE;

    // Check for required properties.
    if (isset($event->message) && isset($event->variables)) {
      // Messages without variables or user specified text.
      if ($event->variables === 'N;') {
        return $event->message;
      }

      return $stringTranslation->translate($event->message, unserialize($event->variables));
    }

    return $message;
  }
}
