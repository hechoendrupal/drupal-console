<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ValidateDependenciesListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class ValidateDependenciesListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function validateDependencies(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $application = $command->getApplication();

        $missingDependencies = $application
            ->getHelperSet()
            ->get("commandDiscovery")
            ->getMissingDependencies();

        $translatorHelper = $application->getTranslator();

        if (!$command instanceof Command) {
            return;
        }

        if ($dependencies = $missingDependencies[$command->getName()]) {
            foreach ($dependencies as $dependency) {
                if (!$application->getValidator()->isModuleInstalled($dependency)) {
                    $errorMessage = sprintf(
                        $translatorHelper->trans('commands.common.errors.module-dependency'),
                        $dependency
                    );
                    $io->error($errorMessage);
                    $event->disableCommand();
                }
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'validateDependencies'];
    }
}
