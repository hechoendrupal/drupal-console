<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\ShowGeneratedFiles.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\AppConsole\Command\GeneratorCommand;

class ShowGeneratedFilesListener implements EventSubscriberInterface
{
   /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGeneratedFiles(ConsoleTerminateEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getHelperSet()->get('message');

        if ($event->getExitCode() != 0) {
            return;
        }

        if ('self-update' == $command->getName()) {
            return;
        }

        if ($command instanceof GeneratorCommand) {
            $files = $command->getGenerator()->getFiles();
            if ($files) {
                $messageHelper->showGeneratedFiles($output, $files);
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGeneratedFiles'];
    }
}
