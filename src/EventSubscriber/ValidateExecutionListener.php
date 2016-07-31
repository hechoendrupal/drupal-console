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

class ValidateExecutionListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function validateExecution(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $application = $command->getApplication();
        $configuration = $application->getConfig();
        $translator = $application->getTranslator();
        $mapping = $configuration->get('application.disable.commands')?:[];

        var_export($mapping);

        if (array_key_exists($command->getName(), $mapping)) {
            $extra = $mapping[$command->getName()];
            $io->commentBlock(
                sprintf(
                    $translator->trans('application.messages.disable.command.error'),
                    $command->getName()
                )
            );
            if ($extra) {
                $io->commentBlock(
                    sprintf(
                        $translator->trans('application.messages.disable.command.extra'),
                        $extra
                    )
                );
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'validateExecution'];
    }
}
