<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowGenerateInlineListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class ShowGenerateInlineListener implements EventSubscriberInterface
{
    private $skipCommands = [
        'self-update',
        'list',
    ];

    private $skipOptions = [
        'env',
        'generate-inline',
    ];

    private $skipArguments = [
        'command'
    ];
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateInline(ConsoleTerminateEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $command_name = $command->getName();

        $this->skipArguments[] = $command_name;

        $application = $command->getApplication();
        $translatorHelper = $application->getTranslator();

        if ($event->getExitCode() != 0) {
            return;
        }

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        // get the input instance
        $input = $event->getInput();

        //Get options list
        $options = array_filter($input->getOptions());

        if (isset($options['generate-inline']) && $options['generate-inline'] == 1) {
            // Remove unnecessary options
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            // Get argument list
            $arguments = array_filter($input->getArguments());
            // Remove unnecessary arguments
            foreach ($this->skipArguments as $remove_argument) {
                unset($arguments[$remove_argument]);
            }

            $inline = '';
            foreach ($arguments as $argument_id => $argument) {
                if (is_array($argument)) {
                    $argument = implode(" ", $argument);
                } elseif (strstr($argument, ' ')) {
                    $argument = '"' . $argument . '"';
                }

                $inline .= " $argument";
            }

            foreach ($options as $option_id => $option) {
                if (strstr($option, ' ')) {
                    $option = '"' . $option . '"';
                }
                $inline.= ' --' . $option_id . '=' . $option;
            }

            // Print yaml output and message
            $io->writeln(
                $translatorHelper->trans('application.console.messages.inline.generated')
            );

            $io->writeln('$ drupal' . $inline);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateInline'];
    }
}
