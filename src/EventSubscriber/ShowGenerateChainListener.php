<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\ShowGeneratedFiles.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\AppConsole\Command\GeneratorCommand;

class ShowGenerateChainListener implements EventSubscriberInterface
{

    private $skipCommands = [
        'self-update',
        'list'
    ];

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateChain(ConsoleTerminateEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getHelperSet()->get('message');
        /** @var TranslatorHelper */
        $translatorHelper = $application->getHelperSet()->get('translator');

        $messageHelper->showMessages($output);

        if ($event->getExitCode() != 0) {
            return;
        }

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        $completedMessageKey = 'application.console.messages.completed';

        if ($command instanceof GeneratorCommand) {
            $completedMessageKey = 'application.console.messages.generated.completed';
        }

        print_r($command->getDefinition()->getArguments());
        print_r($command->getDefinition()->getOptions());
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateChain'];
    }
}
