<?php
/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\CallCommandListener.
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
            if ($chainedCommand['name'] == 'module:install') {
                $messageHelper = $application->getHelperSet()->get('message');
                $translatorHelper = $application->getHelperSet()->get('translator');
                $messageHelper->addErrorMessage(
                  $translatorHelper->trans('commands.chain.messages.module_install')
                );
                continue;
            }

            $callCommand = $application->find($chainedCommand['name']);

            $input = new ArrayInput($chainedCommand['inputs']);
            if (!is_null($chainedCommand['interactive'])) {
                $input->setInteractive($chainedCommand['interactive']);
            }
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
