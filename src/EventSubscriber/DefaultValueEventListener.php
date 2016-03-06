<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\DefaultValueEventListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;

class DefaultValueEventListener implements EventSubscriberInterface
{
    private $skipCommands = [
      'self-update',
      'list',
      'chain'
    ];

    /**
     * @param ConsoleCommandEvent $event
     */
    public function setDefaultValues(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        $application = $command->getApplication();
        $config = $application->getConfig();

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        $input = $command->getDefinition();
        $options = $input->getOptions();
        foreach ($options as $key => $option) {
            $defaultOption = sprintf(
                'application.default.commands.%s.options.%s',
                str_replace(':', '.', $command->getName()),
                $key
            );
            $defaultValue = $config->get($defaultOption);
            if ($defaultValue) {
                $option->setDefault($defaultValue);
            }
        }

        $arguments = $input->getArguments();
        foreach ($arguments as $key => $argument) {
            $defaultArgument = sprintf(
                'application.default.commands.%s.arguments.%s',
                str_replace(':', '.', $command->getName()),
                $key
            );
            $defaultValue = $config->get($defaultArgument);
            if ($defaultValue) {
                $argument->setDefault($defaultValue);
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'setDefaultValues'];
    }
}
