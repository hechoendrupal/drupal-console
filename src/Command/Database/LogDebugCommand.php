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
use Symfony\Component\Console\Command\Command;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Console\Style\DrupalStyle;

class LogDebugCommand extends LogCommandBase
{


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:log:debug')
            ->setDescription($this->trans('commands.database.log.debug.description'));

        $this->addBasicLoggingConfiguration();

        $this->addOption(
              'asc',
              FALSE,
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
            );

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $eventId = $input->getArgument('event-id');
        $eventType = $input->getOption('type');
        $eventSeverity = $input->getOption('severity');
        $userId = $input->getOption('user-id');
        $asc = $input->getOption('asc');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        if ($eventId) {
            $this->getEventDetails($io, $eventId);
        } else {
            $this->getAllEvents($io, $eventType, $eventSeverity, $userId, $asc, $offset, $limit);
        }

        return 0;
    }

    /**
     * @param $io
     * @param $eventId
     * @return bool
     */
    private function getEventDetails(DrupalStyle $io, $eventId)
    {
        $userStorage = $this->entityTypeManager->getStorage('user');
        $severity = RfcLogLevel::getLevels();

        $dblog = $this->database
            ->query(
                'SELECT w.*, u.uid FROM {watchdog} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id',
                [':id' => $eventId]
            )
            ->fetchObject();

        if (!$dblog) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.log.debug.messages.not-found'),
                    $eventId
                )
            );

            return false;
        }

        $user = $userStorage->load($dblog->uid);

        $configuration = [
            $this->trans('commands.database.log.debug.messages.event-id') => $eventId,
            $this->trans('commands.database.log.debug.messages.type') => $dblog->type,
            $this->trans('commands.database.log.debug.messages.date') => $this->dateFormatter->format($dblog->timestamp, 'short'),
            $this->trans('commands.database.log.debug.messages.user') => $user->getUsername() . ' (' . $user->id() .')',
            $this->trans('commands.database.log.debug.messages.severity') => (string) $severity[$dblog->severity],
            $this->trans('commands.database.log.debug.messages.message') => Html::decodeEntities(strip_tags($this->formatMessage($dblog)))
        ];

        $io->writeln(Yaml::encode($configuration));

        return true;
    }

    private function getAllEvents(DrupalStyle $io, $eventType, $eventSeverity, $userId, $asc, $offset, $limit)
    {
        $userStorage = $this->entityTypeManager->getStorage('user');
        $severity = RfcLogLevel::getLevels();

        $query = $this->database->select('watchdog', 'w');
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

        if ($eventType) {
            $query->condition('type', $eventType);
        }

        if ($eventSeverity) {
            if (!in_array($eventSeverity, $severity)) {
                $io->error(
                    sprintf(
                        $this->trans('commands.database.log.debug.messages.invalid-severity'),
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

        if ($asc) {
            $query->orderBy('wid', 'ASC');
        } else {
            $query->orderBy('wid', 'DESC');
        }

        if ($limit) {
            $query->range($offset, $limit);
        }

        $result = $query->execute();

        $tableHeader = $this->createTableHeader();

        $tableRows = [];
        foreach ($result as $dblog) {
          $this->createTableRow($dblog,$userStorage,$this->dateFormatter,$severity);
        }

        $io->table(
            $tableHeader,
            $tableRows
        );

        return true;
    }

}
