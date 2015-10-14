<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowGenerateDocListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        /**
         * @var \Drupal\Console\Command\Command $command
         */
        $command = $event->getCommand();

        $application = $command->getApplication();

        $input = $command->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();

        if (isset($options['generate-doc'])) {
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            $parameters = [
              'options' => $options,
              'arguments' => $arguments,
              'command' => $command->getName(),
              'description' => $command->getDescription(),
              'aliases' => $command->getAliases()
            ];

            $renderedDoc = $application->getRenderHelper()->render(
                'gitbook/generate-doc.md.twig',
                $parameters
            );

            $output = $event->getOutput();
            $output->writeln($renderedDoc);

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
