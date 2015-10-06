<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RestDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;

class DBLogClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dblog:clear')
            ->setDescription($this->trans('commands.dblog.clear.description'))
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $event_id = $input->getArgument('event-id');
        $event_type = $input->getOption('type');
        $event_severity = $input->getOption('severity');
        $user_id = $input->getOption('user-id');

        if ($event_id) {
            $this->clearEvent($output, $event_id);
        } else {
            $this->clearEvents($event_type, $event_severity, $user_id, $output);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $resource_id    String
     */
    private function clearEvent($output, $event_id)
    {
        $connection = $this->getDatabase();
        $date_formatter = $this->getDateFormatter();

        $result = $connection->delete('watchdog')->condition('wid', $event_id)->execute();

        if (!$result) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.dblog.debug.messages.not-found'),
                    $event_id
                ).'</error>'
            );

            return false;
        } else {
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.dblog.clear.messages.event-deleted'),
                    $event_id
                ).'</info>'
            );

            return true;
        }
    }

    protected function clearEvents($event_type, $event_severity, $user_id,  $output)
    {
        $table = $this->getTableHelper();
        $table->setlayout($table::LAYOUT_COMPACT);

        $connection = $this->getDatabase();
        $date_formatter = $this->getDateFormatter();
        $user_storage = $this->getEntityManager()->getStorage('user');
        $severity = RfcLogLevel::getLevels();

        $query = $connection->delete('watchdog');

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

        $result = $query->execute();

        if (!$result) {
            $output->writeln(
                '[+] <error>'.
                $this->trans('commands.dblog.clear.messages.clear-error')
                .'</error>'
            );

            return false;
        } else {
            $output->writeln(
                '[+] <info>'.
                $this->trans('commands.dblog.clear.messages.clear-sucess')
                .'</info>'
            );

            return true;
        }
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
