<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\ShowWelcomeMessage.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShowWelcomeMessageListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function showMessage(ConsoleCommandEvent $event)
    {
        /**
         * @var \Drupal\AppConsole\Command\Command $command
         */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getHelperSet()->get('message');
        /** @var TranslatorHelper */
        $translatorHelper = $application->getHelperSet()->get('translator');

        $welcomeMessageKey = 'commands.'.str_replace(':', '.', $command->getName()).'.welcome';
        $welcomeMessage = $translatorHelper->trans($welcomeMessageKey);

        if ($welcomeMessage != $welcomeMessageKey) {
            $messageHelper->showMessage($output, $welcomeMessage);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showMessage'];
    }
}
