<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowGeneratedFilesListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\GeneratorCommand;

class ShowGeneratedFilesListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGeneratedFiles(ConsoleTerminateEvent $event)
    {
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $showFileHelper = $application->getShowFileHelper();

        if ($event->getExitCode() != 0) {
            return;
        }

        if ('self-update' == $command->getName()) {
            return;
        }

        if ($command instanceof GeneratorCommand) {
            $files = $command->getGenerator()->getFiles();
            if ($files) {
                $showFileHelper->generatedFiles($output, $files);
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
