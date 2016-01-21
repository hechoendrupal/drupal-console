<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowWelcomeMessageListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowWelcomeMessageListener
 * @package Drupal\Console\EventSubscriber
 */
class ShowWelcomeMessageListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function showWelcomeMessage(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();

        $input = $command->getDefinition();

        if ($input->hasOption('generate-doc')) {
            return;
        }

        if ($input->hasOption('no-interaction')) {
            return;
        }

        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $application = $command->getApplication();
        $translatorHelper = $application->getTranslator();

        $welcomeMessageKey = 'commands.'.str_replace(':', '.', $command->getName()).'.welcome';
        $welcomeMessage = $translatorHelper->trans($welcomeMessageKey);

        if ($welcomeMessage != $welcomeMessageKey) {
            $io->text($welcomeMessage);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showWelcomeMessage'];
    }
}
