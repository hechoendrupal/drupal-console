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
use Drupal\AppConsole\Command\Command;
use Drupal\AppConsole\Command\GeneratorCommand;

class ShowGeneratedFiles implements EventSubscriberInterface
{

    /**
     * @var TranslatorHelper
     */
    protected $trans;

    /**
     * @var string
     */
    protected $completedMessageKey = 'application.console.messages.completed';

    /**
     * @param TranslatorHelper $trans
     */
    public function __construct(TranslatorHelper $trans)
    {
        $this->trans = $trans;
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGeneratedFiles'];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function showGeneratedFiles(ConsoleTerminateEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();

        if ($event->getExitCode() != 0) {
            return;
        }

        $completedMessageKey = 'application.console.messages.completed';

        if ('self-update' == $command->getName()) {
            return;
        }

        if ($command instanceof Command) {
            $messages = $command->getMessages();

            foreach ($messages as $message) {
                $command->showMessage($output, $this->trans($message));
            }
        }

        if ($command instanceof GeneratorCommand) {
            $files = $command->getGenerator()->getFiles();
            if ($files) {
                $command->showGeneratedFiles($output, $files);
            }
            $completedMessageKey = 'application.console.messages.generated.completed';
        }

        if ($command instanceof Command) {
            $completedMessage = $this->trans->trans($completedMessageKey);
            if ($completedMessage != $completedMessageKey) {
                $command->showMessage($output, $completedMessage);
            }
        }
    }
}
