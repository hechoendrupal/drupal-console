<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\DefaultValueEventListener.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultValueEventListener implements EventSubscriberInterface
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function setDefaultValues(ConsoleCommandEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        /** @var \Drupal\AppConsole\Console\Application $command */
        $application = $command->getApplication();
        /** @var \Drupal\AppConsole\Config $config */
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
