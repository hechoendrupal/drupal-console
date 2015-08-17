<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowCompletedMessageListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\GeneratorCommand;

class ShowCompletedMessageListener implements EventSubscriberInterface
{
    private $skipCommands = [
        'self-update',
        'list',
    ];

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showCompletedMessage(ConsoleTerminateEvent $event)
    {
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getMessageHelper();
        $translatorHelper = $application->getTranslator();

        $messageHelper->showMessages($output);

        if ($event->getExitCode() != 0) {
            return;
        }

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        /*$completedMessageKey = 'application.console.messages.completed';

        if ($command instanceof GeneratorCommand) {
            $completedMessageKey = 'application.console.messages.generated.completed';
        }

        $completedMessage = $translatorHelper->trans($completedMessageKey);
        if ($completedMessage != $completedMessageKey) {
            $messageHelper->showMessage($output, $completedMessage);
        }*/
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showCompletedMessage'];
    }
}
