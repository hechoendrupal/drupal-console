<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowGenerateChainListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Dumper;

/**
 * Class ShowGenerateChainListener
 * @package Drupal\Console\EventSubscriber
 */
class ShowGenerateChainListener implements EventSubscriberInterface
{
    /* @var array */
    private $skipCommands = [
        'self-update',
        'list',
    ];

    /* @var array */
    private $skipOptions = [
        'env',
        'generate-chain',
    ];

    /* @var array */
    private $skipArguments = [
        'command'
    ];

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateChain(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        /* @var \Drupal\Console\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();
        $command_name = $command->getName();

        $this->skipArguments[] = $command_name;

        $application = $command->getApplication();
        $translatorHelper = $application->getTranslator();

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        // get the input instance
        $input = $event->getInput();

        if ($input->getOption('generate-chain')) {
            $options = array_filter($input->getOptions());
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            $arguments = array_filter($input->getArguments());
            foreach ($this->skipArguments as $remove_argument) {
                unset($arguments[$remove_argument]);
            }

            $yaml = [];
            if ($options) {
                $yaml[$command_name]['options'] = $options;
            }
            if ($arguments) {
                $yaml[$command_name]['arguments'] = $arguments;
            }

            $dumper = new Dumper();
            $yaml = $dumper->dump($yaml, 10);

            $yaml = str_replace(
                sprintf('\'%s\':', $command_name),
                sprintf('  - command: %s', $command_name),
                $yaml
            );

            $output->commentBlock(
                $translatorHelper->trans('application.console.messages.chain.generated')
            );

            $output->writeln($yaml);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateChain'];
    }
}
