<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowGenerateDocListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowGenerateDocListener
 * @package Drupal\Console\EventSubscriber
 */
class ShowGenerateDocListener implements EventSubscriberInterface
{
    private $skipOptions = [
        'generate-doc'
    ];

    /**
     * @param ConsoleCommandEvent $event
     * @return void
     */
    public function showGenerateDoc(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();

        /* @var DrupalStyle $io */
        $io = $event->getOutput();

        $input = $command->getDefinition();
        if ($input->hasOption('generate-doc')) {
            $options = $input->getOptions();
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            $arguments = $input->getArguments();

            $parameters = [
              'options' => $options,
              'arguments' => $arguments,
              'command' => $command->getName(),
              'aliases' => $command->getAliases(),
              'examples' => [],
              'messages' => [
                    'command_description' => sprintf($command->trans('commands.generate.doc.command.command_description'), $command->getName(), $command->getDescription()),
                    'usage' =>  $command->trans('commands.generate.doc.command.usage'),
                    'options' => $command->trans('commands.generate.doc.command.options'),
                    'option' => $command->trans('commands.generate.doc.command.options'),
                    'details' => $command->trans('commands.generate.doc.command.details'),
                    'arguments' => $command->trans('commands.generate.doc.command.arguments'),
                    'argument' => $command->trans('commands.generate.doc.command.argument'),
              ]
            ];

            $renderedDoc = $command->getRenderHelper()->render(
                'gitbook/generate-doc.md.twig',
                $parameters
            );

            $io->writeln($renderedDoc);

            $event->disableCommand();
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showGenerateDoc'];
    }
}
