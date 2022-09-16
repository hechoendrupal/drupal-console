<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\LogClearCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\RfcLogLevel;

class LogClearCommand extends Command
{
    /**
     * @var Connection
     */
    protected $database;

    /**
     * LogClearCommand constructor.
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
            ->setName('database:log:clear')
            ->setDescription($this->trans('commands.database.log.clear.description'))
            ->addArgument(
                'event-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.log.clear.arguments.event-id')
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.clear.options.type')
            )
            ->addOption(
                'severity',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.clear.options.severity')
            )
            ->addOption(
                'user-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.clear.options.user-id')
            )
            ->setAliases(['dblc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventId = $input->getArgument('event-id');
        $eventType = $input->getOption('type');
        $eventSeverity = $input->getOption('severity');
        $userId = $input->getOption('user-id');

        if ($eventId) {
            $this->clearEvent($eventId);
        } else {
            $this->clearEvents($eventType, $eventSeverity, $userId);
        }

        return 0;
    }

    /**
     * @param $eventId
     * @return bool
     */
    private function clearEvent($eventId)
    {
        $result = $this->database->delete('watchdog')->condition('wid', $eventId)->execute();

        if (!$result) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.database.log.clear.messages.not-found'),
                    $eventId
                )
            );

            return false;
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.database.log.clear.messages.event-deleted'),
                $eventId
            )
        );

        return true;
    }

    /**
     * @param $eventType
     * @param $eventSeverity
     * @param $userId
     * @return bool
     */
    protected function clearEvents($eventType, $eventSeverity, $userId)
    {
        $severity = RfcLogLevel::getLevels();
        $query = $this->database->delete('watchdog');

        if ($eventType) {
            $query->condition('type', $eventType);
        }

        if ($eventSeverity) {
            if (!in_array($eventSeverity, $severity)) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.database.log.clear.messages.invalid-severity'),
                        $eventSeverity
                    )
                );

                return false;
            }

            $query->condition('severity', array_search($eventSeverity, $severity));
        }

        if ($userId) {
            $query->condition('uid', $userId);
        }

        $result = $query->execute();

        if (!$result) {
            $this->getIo()->error(
                $this->trans('commands.database.log.clear.messages.clear-error')
            );

            return false;
        }

        $this->getIo()->success(
            $this->trans('commands.database.log.clear.messages.clear-sucess')
        );

        return true;
    }
}
