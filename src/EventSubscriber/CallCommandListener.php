<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\CallCommandListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Drupal\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;

class CallCommandListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function callCommands(ConsoleTerminateEvent $event)
    {
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        $output = $event->getOutput();

        if (!$command instanceof Command) {
            return;
        }

        $application = $command->getApplication();
        $commands = $application->getChain()->getCommands();

        if (!$commands) {
            return;
        }

        foreach ($commands as $chainedCommand) {
            $callCommand = $application->find($chainedCommand['name']);

            $input = new ArrayInput($chainedCommand['inputs']);
            if (!is_null($chainedCommand['interactive'])) {
                $input->setInteractive($chainedCommand['interactive']);
            }
            $callCommand->run($input, $output);

            $drupal = $application->getDrupalHelper();
            if ($chainedCommand['name'] === 'site:new') {
                if ($chainedCommand['inputs']['site-name']) {
                    $siteRoot = sprintf(
                        '%s/%s', getcwd(),
                        $chainedCommand['inputs']['site-name']
                    );
                    chdir($siteRoot);
                }
                $drupal->isValidRoot(getcwd());
                $drupal->getAutoLoadClass();
                $application->prepare($drupal);
            }

            if ($chainedCommand['name'] === 'site:install') {
                $drupal->isValidRoot(getcwd());
                $application->prepare($drupal);
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'callCommands'];
    }
}
