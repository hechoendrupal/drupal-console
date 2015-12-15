<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowWelcomeMessageListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Style\DrupalStyle;

class ShowWelcomeMessageListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function showMessage(ConsoleCommandEvent $event)
    {
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        $input = $event->getInput();
        $output = $event->getOutput();

        $output = new DrupalStyle($input, $output);

        $application = $command->getApplication();
        $translatorHelper = $application->getTranslator();

        $welcomeMessageKey = 'commands.'.str_replace(':', '.', $command->getName()).'.welcome';
        $welcomeMessage = $translatorHelper->trans($welcomeMessageKey);

        if ($welcomeMessage != $welcomeMessageKey) {
            $output->text($welcomeMessage);
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
