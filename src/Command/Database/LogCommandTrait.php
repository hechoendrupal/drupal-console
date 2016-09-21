<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 21/09/16
 * Time: 11:08
 */

namespace Drupal\Console\Command\Database;

use Drupal\user\Entity\User;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\Console\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Symfony\Component\Console\Output\OutputInterface;

trait LogCommandTrait {

  use ContainerAwareCommandTrait;

  /**
   * @var Connection
   */
  private $database;

  /**
   * @var DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var TranslatorInterface
   */
  private $stringTranslation;

  /**
   * @var
   */
  private $userStorage;

  /**
   * Adds an argument.
   *
   * @param string $name        The argument name
   * @param int    $mode        The argument mode: InputArgument::REQUIRED or InputArgument::OPTIONAL
   * @param string $description A description text
   * @param mixed  $default     The default value (for InputArgument::OPTIONAL mode only)
   *
   * @return Command The current instance
   */
  abstract public function addArgument($name, $mode = null, $description = '', $default = null);

  /**
   * Adds an option.
   *
   * @param string $name        The option name
   * @param string $shortcut    The shortcut (can be null)
   * @param int    $mode        The option mode: One of the InputOption::VALUE_* constants
   * @param string $description A description text
   * @param mixed  $default     The default value (must be null for InputOption::VALUE_NONE)
   *
   * @return Command The current instance
   */
  abstract public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null);

  /**
   * @return \Drupal\Core\Database\Connection
   */
  public function getDatabase() {
    if($this->database === null){
      $this->database = $this->getDrupalService('database');
    }
    return $this->database;
  }

  /**
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   */
  public function getDateFormatter() {
    if($this->dateFormatter === null){
      $this->dateFormatter = $this->getDrupalService('date.formatter');
    }
    return $this->dateFormatter;
  }

  /**
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public function getEntityTypeManager(){
    if($this->entityTypeManager === null){
      $this->entityTypeManager = $this->getDrupalService('entity_type.manager');
    }
    return $this->entityTypeManager;
  }

  /**
   * @return \Drupal\Core\StringTranslation\Translator\TranslatorInterface
   */
  public function getStringTranslation() {
    if($this->stringTranslation === null){
      $this->stringTranslation = $this->getDrupalService('string_translation');
    }
    return $this->stringTranslation;
  }

  public function getUserStorage()
  {
    if($this->userStorage === null){
      $this->userStorage = $this->getEntityTypeManager()->getStorage('user');
    }
    return $this->userStorage;
  }




  public function addBasicLoggingConfiguration() {
    $this
      ->addArgument(
        'event-id',
        InputArgument::OPTIONAL,
        $this->trans('commands.database.log.debug.arguments.event-id')
      )
      ->addOption(
        'type',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.database.log.debug.options.type')
      )
      ->addOption(
        'severity',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.database.log.debug.options.severity')
      )
      ->addOption(
        'user-id',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.database.log.debug.options.user-id')
      );
  }

  protected function createTableHeader()
  {
    return [
      $this->trans('commands.database.log.debug.messages.event-id'),
      $this->trans('commands.database.log.debug.messages.type'),
      $this->trans('commands.database.log.debug.messages.date'),
      $this->trans('commands.database.log.debug.messages.message'),
      $this->trans('commands.database.log.debug.messages.user'),
      $this->trans('commands.database.log.debug.messages.severity'),
    ];
  }

  protected function createTableRow($dblog, $severity) {

    /** @var User $user */
    $user = $this->getUserStorage()->load($dblog->uid);

    return [
      $dblog->wid,
      $dblog->type,
      $this->getDateFormatter()->format($dblog->timestamp, 'short'),
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
    $message = FALSE;

    // Check for required properties.
    if (isset($event->message) && isset($event->variables)) {
      // Messages without variables or user specified text.
      if ($event->variables === 'N;') {
        return $event->message;
      }

      return $this->getStringTranslation()->translate($event->message, unserialize($event->variables));
    }

    return $message;
  }

}