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

        $input = $event->getInput();

        if ($input->getOption('generate-inline')) {
            $options = array_filter($input->getOptions());
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            $arguments = array_filter($input->getArguments());
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

            // Refactor and remove nested levels. Then apply to arguments.
            foreach ($options as $optionName => $optionValue) {
                if (is_array($optionValue)) {
                    foreach ($optionValue as $optionItem) {
                        if (is_array($optionItem)) {
                            $inlineValue = implode(
                                ' ', array_map(
                                    function ($v, $k) {
                                        return $k . ':' . $v;
                                    },
                                    $optionItem,
                                    array_keys($optionItem)
                                )
                            );
                        } else {
                            $inlineValue = $optionItem;
                        }
                        $inline .= ' --' . $optionName . '="' . $inlineValue . '"';
                    }
                } else {
                    if (is_bool($optionValue)) {
                        $inline.= ' --' . $optionName;
                    } else {
                        $inline.= ' --' . $optionName . '="' . $optionValue . '"';
                    }
                }
            }

            // Print yaml output and message
            $io->commentBlock(
                $translatorHelper->trans('application.messages.inline.generated')
            );

            $io->writeln(
                sprintf(
                    '$ drupal %s %s',
                    $command_name,
                    $inline
                )
            );
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
