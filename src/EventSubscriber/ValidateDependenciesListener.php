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

class ValidateDependenciesListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function validateDependencies(ConsoleCommandEvent $event)
    {
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getMessageHelper();
        $translatorHelper = $application->getTranslator();

        if (!$command instanceof Command) {
            return;
        }

        $dependencies = $command->getDependencies();

        if ($dependencies) {
            foreach ($dependencies as $dependency) {
                if ($application->getValidator()->validateModuleInstalled($dependency) === false) {
                    $errorMessage = sprintf(
                        $translatorHelper->trans('commands.common.errors.module-dependency'),
                        $dependency
                    );
                    $messageHelper->showMessage($output, $errorMessage, 'error');
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
