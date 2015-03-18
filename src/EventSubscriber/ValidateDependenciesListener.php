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
use Drupal\AppConsole\Command\Command;

class ValidateDependenciesListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function validateDependencies(ConsoleCommandEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();

        $application = $command->getApplication();
        $messageHelper = $application->getHelperSet()->get('message');
        /** @var TranslatorHelper */
        $translatorHelper = $application->getHelperSet()->get('translator');

        if (!$command instanceof Command) {
            return;
        }

        $dependencies = $command->getDependencies();

        if ($dependencies) {
            foreach ($dependencies as $dependency) {
                if (\Drupal::moduleHandler()->moduleExists($dependency) === false) {
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
