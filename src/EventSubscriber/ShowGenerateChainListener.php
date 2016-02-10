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
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

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

        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $command_name = $command->getName();

        $this->skipArguments[] = $command_name;

        $application = $command->getApplication();
        $translatorHelper = $application->getTranslator();

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

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

            $commandDefinition = [];
            if ($options) {
                $commandDefinition[$command_name]['options'] = $options;
            }
            if ($arguments) {
                $commandDefinition[$command_name]['arguments'] = $arguments;
            }

            $dumper = new Dumper();
            $yml = $dumper->dump($commandDefinition, 10);

            $yml = str_replace(
                sprintf('\'%s\':', $command_name),
                sprintf('  - command: %s', $command_name),
                $yml
            );

            $io->commentBlock(
                $translatorHelper->trans('application.messages.chain.generated')
            );

            $io->writeln($yml);
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
