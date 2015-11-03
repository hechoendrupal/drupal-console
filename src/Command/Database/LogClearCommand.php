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
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Core\Logger\RfcLogLevel;

class LogClearCommand extends ContainerAwareCommand
{
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
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.clear.options.type')
            )
            ->addOption(
                'severity',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.clear.options.severity')
            )
            ->addOption(
                'user-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.log.clear.options.user-id')
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
     * @param $output
     * @param $event_id
     * @return bool
     */
    private function clearEvent($output, $event_id)
    {
        $connection = $this->getDatabase();

        $result = $connection->delete('watchdog')->condition('wid', $event_id)->execute();

        if (!$result) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.database.log.clear.messages.not-found'),
                    $event_id
                ).'</error>'
            );

            return false;
        } else {
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.database.log.clear.messages.event-deleted'),
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
                    $this->trans('commands.database.log.clear.messages.invalid-severity'),
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
                $this->trans('commands.database.log.clear.messages.clear-error')
                .'</error>'
            );

            return false;
        } else {
            $output->writeln(
                '[+] <info>'.
                $this->trans('commands.database.log.clear.messages.clear-sucess')
                .'</info>'
            );

            return true;
        }
    }
}
