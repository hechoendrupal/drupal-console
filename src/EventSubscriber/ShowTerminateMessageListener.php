<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowTerminateMessageListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class ShowTerminateMessageListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showTerminateMessages(ConsoleTerminateEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $application = $command->getApplication();

        if ($errorMessage = $application->getErrorMessage()) {
            $io->warning($errorMessage);
        }

        if ($event->getExitCode() != 0) {
            return;
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showTerminateMessages'];
    }
}
