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
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Console\Style\DrupalStyle;

class LogDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('database:log:debug')
            ->setDescription($this->trans('commands.database.log.debug.description'))
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
            )
            ->addOption(
                'reverse',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.database.log.debug.options.reverse')
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $eventId = $input->getArgument('event-id');
        $eventType = $input->getOption('type');
        $eventSeverity = $input->getOption('severity');
        $userId = $input->getOption('user-id');
        $reverse = $input->getOption('reverse');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        if ($eventId) {
            $this->getEventDetails($io, $eventId);
        } else {
            $this->getAllEvents($io, $eventType, $eventSeverity, $userId, $reverse, $offset, $limit);
        }
    }

    /**
     * @param $io
     * @param $eventId
     * @return bool
     */
    private function getEventDetails(DrupalStyle $io, $eventId)
    {
        $connection = $this->getDatabase();
        $dateFormatter = $this->getDateFormatter();
        $userStorage = $this->getEntityManager()->getStorage('user');
        $severity = RfcLogLevel::getLevels();

        $dblog = $connection->query('SELECT w.*, u.uid FROM {watchdog} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id', array(':id' => $eventId))->fetchObject();


        if (!$dblog) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.log.debug.messages.not-found'),
                    $eventId
                )
            );

            return false;
        }

        $user= $userStorage->load($dblog->uid);

        $configuration = [
            $this->trans('commands.database.log.debug.messages.event-id') => $eventId,
            $this->trans('commands.database.log.debug.messages.type') => $dblog->type,
            $this->trans('commands.database.log.debug.messages.date') => $dateFormatter->format($dblog->timestamp, 'short'),
            $this->trans('commands.database.log.debug.messages.user') => $user->getUsername() . ' (' . $user->id() .')',
            $this->trans('commands.database.log.debug.messages.severity') => (string) $severity[$dblog->severity],
            $this->trans('commands.database.log.debug.messages.message') => Html::decodeEntities(strip_tags($this->formatMessage($dblog)))
        ];

        $io->writeln(Yaml::encode($configuration));

        return true;
    }

    protected function getAllEvents(DrupalStyle $io, $eventType, $eventSeverity, $userId, $reverse, $offset, $limit)
    {
        $connection = $this->getDatabase();
        $dateFormatter = $this->getDateFormatter();
        $userStorage = $this->getEntityManager()->getStorage('user');
        $severity = RfcLogLevel::getLevels();

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

        if ($reverse) {
            $query->orderBy('wid', 'DESC');
        }

        if ($limit) {
            $query->range($offset, $limit);
        }

        $result = $query->execute();

        $tableHeader = [
            $this->trans('commands.database.log.debug.messages.event-id'),
            $this->trans('commands.database.log.debug.messages.type'),
            $this->trans('commands.database.log.debug.messages.date'),
            $this->trans('commands.database.log.debug.messages.message'),
            $this->trans('commands.database.log.debug.messages.user'),
            $this->trans('commands.database.log.debug.messages.severity'),
        ];

        $tableRows = [];
        foreach ($result as $dblog) {
            $user= $userStorage->load($dblog->uid);

            $tableRows[] = [
                $dblog->wid,
                $dblog->type,
                $dateFormatter->format($dblog->timestamp, 'short'),
                Unicode::truncate(Html::decodeEntities(strip_tags($this->formatMessage($dblog))), 56, true, true),
                $user->getUsername() . ' (' . $user->id() .')',
                $severity[$dblog->severity]
            ];
        }

        $io->table(
            $tableHeader,
            $tableRows
        );

        return true;
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
    public function formatMessage($event)
    {
        $stringTranslation = $this->getStringTanslation();
        $message = false;

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
