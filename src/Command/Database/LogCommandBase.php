<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 21/09/16
 * Time: 11:08
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\Console\Command\Shared\CommandTrait;

class LogCommandBase extends Command {

  use CommandTrait;

  use ContainerAwareCommandTrait;

  use LogCommandTrait;


  /**
   * @var Connection
   */
  protected $database;

  /**
   * @var DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var TranslatorInterface
   */
  protected $stringTranslation;

  /**
   * LogDebugCommand constructor.
   * @param Connection $database
   * @param DateFormatterInterface $dateFormatter
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param TranslatorInterface $stringTranslation
   */
  public function __construct(
    Connection $database,
    DateFormatterInterface $dateFormatter,
    EntityTypeManagerInterface $entityTypeManager,
    TranslatorInterface $stringTranslation
  ) {
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->stringTranslation = $stringTranslation;
    parent::__construct();
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

  protected function createTableRow($dblog, $userStorage, $dateFormatter, $severity) {

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
    $message = FALSE;

    // Check for required properties.
    if (isset($event->message) && isset($event->variables)) {
      // Messages without variables or user specified text.
      if ($event->variables === 'N;') {
        return $event->message;
      }

      return $this->stringTranslation->translate($event->message, unserialize($event->variables));
    }

    return $message;
  }

}