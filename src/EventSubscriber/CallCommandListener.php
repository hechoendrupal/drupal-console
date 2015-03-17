<?php
/**
 * Created by PhpStorm.
 * User: jmolivas
 * Date: 3/17/15
 * Time: 2:05 PM
 */

namespace Drupal\AppConsole\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Drupal\AppConsole\Command\Command;
use Symfony\Component\Console\ConsoleEvents;

class CallCommandListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function callCommands(ConsoleTerminateEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();

        if (!$command instanceof Command) {
            return;
        }

        $commands = $command->getHelper('chain')->getCommands();

        if (!$commands) {
            return;
        }

        $application = $command->getApplication();
        foreach ($commands as $chainedCommand) {
            $callCommand = $application->find($chainedCommand['name']);

            $input = new ArrayInput($chainedCommand['arguments']);
            $callCommand->run($input, $output);
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
