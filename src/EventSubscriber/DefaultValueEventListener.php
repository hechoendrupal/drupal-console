<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\DefaultValueEventListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();
        /**
         * @var \Drupal\Console\Console\Application $command
         */
        $application = $command->getApplication();
        /**
         * @var \Drupal\Console\Config $config
         */
        $config = $application->getConfig();

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        $input = $command->getDefinition();
        $options = $input->getOptions();
        foreach ($options as $key => $option) {
            $defaultOption = 'commands.' . str_replace(':', '.', $command->getName()) . '.options.' . $key;
            $defaultValue = $config->get($defaultOption);
            if ($defaultValue) {
                $option->setDefault($defaultValue);
            }
        }

        $arguments = $input->getArguments();
        foreach ($arguments as $key => $argument) {
            $defaultArgument = 'commands.' . str_replace(':', '.', $command->getName()) . '.arguments.' . $key;
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
