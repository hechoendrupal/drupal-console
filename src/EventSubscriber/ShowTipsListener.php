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


        // pick randomly one of the tips (5 tips as maximum).
        $tips = $this->get_tip($translatorHelper, $command);

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

    private function get_tip($translatorHelper, $command)
    {
        $first_tip = $get_tip = $translatorHelper->trans('commands.'.str_replace(':', '.', $command->getName()).'.tips.0.tip');
        preg_match("/^commands./", $get_tip, $matches, null, 0);
        if (!empty($matches)) {
            return false;
        }

        $n = rand(0, 5);
        $get_tip = $translatorHelper->trans('commands.'.str_replace(':', '.', $command->getName()).'.tips.' . $n . '.tip');
        preg_match("/^commands./", $get_tip, $matches, null, 0);

        if (empty($matches)) {
            return $get_tip;
        } else {
            return $this->get_tip($translatorHelper, $command);
        }
    }
}
