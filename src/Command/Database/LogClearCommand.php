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
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Console\Style\DrupalStyle;

class LogClearCommand extends Command
{
    use ContainerAwareCommandTrait;
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

        if ($eventId) {
            $this->clearEvent($io, $eventId);
        } else {
            $this->clearEvents($io, $eventType, $eventSeverity, $userId);
        }
    }


    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param $eventId
     * @return bool
     */
    private function clearEvent(DrupalStyle $io, $eventId)
    {
        $connection = $this->getDrupalService('database');

        $result = $connection->delete('watchdog')->condition('wid', $eventId)->execute();

        if (!$result) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.log.clear.messages.not-found'),
                    $eventId
                )
            );

            return false;
        }

        $io->success(
            sprintf(
                $this->trans('commands.database.log.clear.messages.event-deleted'),
                $eventId
            )
        );

        return true;
    }

    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param $eventType
     * @param $eventSeverity
     * @param $userId
     * @return bool
     */
    protected function clearEvents(DrupalStyle $io, $eventType, $eventSeverity, $userId)
    {
        $connection = $this->getDrupalService('database');
        $severity = RfcLogLevel::getLevels();

        $query = $connection->delete('watchdog');

        if ($eventType) {
            $query->condition('type', $eventType);
        }

        if ($eventSeverity) {
            if (!in_array($eventSeverity, $severity)) {
                $io->error(
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
            $io->error(
                $this->trans('commands.database.log.clear.messages.clear-error')
            );

            return false;
        }

        $io->success(
            $this->trans('commands.database.log.clear.messages.clear-sucess')
        );

        return true;
    }
}
