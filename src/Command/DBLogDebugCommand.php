<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;

class DBLogDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dblog:debug')
            ->setDescription($this->trans('commands.dblog.debug.description'))
            ->addArgument(
                'event-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.dblog.debug.arguments.event-id')
            )
            ->addOption(
                'type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.dblog.debug.options.type')
            )
            ->addOption(
                'severity',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.dblog.debug.options.severity')
            )
            ->addOption(
                'user-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.dblog.debug.options.user-id')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.dblog.debug.options.limit')
            )
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.dblog.debug.options.offset')
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $event_id = $input->getArgument('event-id');
        $event_type = $input->getOption('type');
        $event_severity = $input->getOption('severity');
        $user_id = $input->getOption('user-id');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        if ($event_id) {
            $this->getEventDetails($output, $event_id);
        } else {
            $this->getAllEvents($event_type, $event_severity, $user_id, $offset, $limit, $output);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $resource_id    String
     */
    private function getEventDetails($output, $event_id)
    {
        $connection = $this->getDatabase();
        $date_formatter = $this->getDateFormatter();
        $user_storage = $this->getEntityManager()->getStorage('user');
        $severity = RfcLogLevel::getLevels();

        $dblog = $connection->query('SELECT w.*, u.uid FROM {watchdog} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id', array(':id' => $event_id))->fetchObject();


        if (empty($dblog)) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.dblog.debug.messages.not-found'),
                    $event_id
                ).'</error>'
            );

            return false;
        }

        $user= $user_storage->load($dblog->uid);

        $configuration = array();
        $configuration[$this->trans('commands.dblog.debug.messages.event-id')] = $event_id;
        $configuration[$this->trans('commands.dblog.debug.messages.type')] = $dblog->type;
        $configuration[$this->trans('commands.dblog.debug.messages.date')] = $date_formatter->format($dblog->timestamp, 'short');
        $configuration[$this->trans('commands.dblog.debug.messages.user')] = $user->getUsername() . ' (' . $user->id() .')';
        $configuration[$this->trans('commands.dblog.debug.messages.severity')] = (string) $severity[$dblog->severity];
        $configuration[$this->trans('commands.dblog.debug.messages.message')] = Html::decodeEntities(strip_tags($this->formatMessage($dblog)));

        $configurationEncoded = Yaml::encode($configuration);

        $output->writeln($configurationEncoded);
    }

    protected function getAllEvents($event_type, $event_severity, $user_id, $offset, $limit, $output)
    {
        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        $connection = $this->getDatabase();
        $date_formatter = $this->getDateFormatter();
        $user_storage = $this->getEntityManager()->getStorage('user');
        $severity = RfcLogLevel::getLevels();

        $query = $connection->select('watchdog', 'w');
        $query->fields(
            'w', array(
            'wid',
            'uid',
            'severity',
            'type',
            'timestamp',
            'message',
            'variables',
            )
        );

        if (!empty($event_type)) {
            $query->condition('type', $event_type);
        }

        if (!empty($event_severity) && in_array($event_severity, $severity)) {
            $query->condition('severity', array_search($event_severity, $severity));
        } elseif (!empty($event_severity)) {
            $output->writeln(
                '[-] <error>' .
                sprintf(
                    $this->trans('commands.dblog.debug.messages.invalid-severity'),
                    $event_severity
                )
                . '</error>'
            );
        }

        if (!empty($user_id)) {
            $query->condition('uid', $user_id);
        }

        if (!$offset) {
            $offset = 0;
        }

        if ($limit) {
            $query->range($offset, $limit);
        }

        $result = $query->execute();

        $table->setHeaders(
            [
              $this->trans('commands.dblog.debug.messages.event-id'),
              $this->trans('commands.dblog.debug.messages.type'),
              $this->trans('commands.dblog.debug.messages.date'),
              $this->trans('commands.dblog.debug.messages.message'),
              $this->trans('commands.dblog.debug.messages.user'),
              $this->trans('commands.dblog.debug.messages.severity'),
            ]
        );

        $table->setlayout($table::LAYOUT_COMPACT);

        foreach ($result as $dblog) {
            $user= $user_storage->load($dblog->uid);

            $table->addRow(
                [
                    $dblog->wid,
                    $dblog->type,
                    $date_formatter->format($dblog->timestamp, 'short'),
                    Unicode::truncate(Html::decodeEntities(strip_tags($this->formatMessage($dblog))), 56, true, true),
                    $user->getUsername() . ' (' . $user->id() .')',
                    $severity[$dblog->severity]
                ]
            );
        }

        $table->render($output);
    }

    /**
     * Formats a database log message.
     *
     * @param object $row
     *   The record from the watchdog table. The object properties are: wid, uid,
     *   severity, type, timestamp, message, variables, link, name.
     *
     * @return string|false
     *   The formatted log message or FALSE if the message or variables properties
     *   are not set.
     */
    public function formatMessage($event)
    {
        $string_translation = $this->getStringTanslation();

        // Check for required properties.
        if (isset($event->message) && isset($event->variables)) {
            // Messages without variables or user specified text.
            if ($event->variables === 'N;') {
                $message = $event->message;
            }
            // Message to translate with injected variables.
            else {
                $message = $string_translation->translate($event->message, unserialize($event->variables));
            }
        } else {
            $message = false;
        }
        return $message;
    }
}
