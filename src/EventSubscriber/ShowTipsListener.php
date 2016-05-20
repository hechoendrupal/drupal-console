<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowTipsListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowTipsListener
 * @package Drupal\Console\EventSubscriber
 */
class ShowTipsListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function showTips(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();

        $input = $command->getDefinition();


        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $application = $command->getApplication();
        $translatorHelper = $application->getTranslator();

        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        //@TODO: pick randomly one of the tips
        $tips = $translatorHelper->trans('commands.'.str_replace(':', '.', $command->getName()).'.tips.0.tip');
        if ($learning && $tips) {
          $io->commentBlock($tips);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showTips'];
    }
}
