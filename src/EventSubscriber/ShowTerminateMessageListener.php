<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowTerminateMessageListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShowTerminateMessageListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showTerminateMessages(ConsoleTerminateEvent $event)
    {
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getMessageHelper();

        $messageHelper->showMessages($output);

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
