<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\ShowGenerateDocListener.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ShowGenerateDocListener
 * @package Drupal\AppConsole\EventSubscriber
 */
class ShowGenerateDocListener implements EventSubscriberInterface
{
    private $skipCommands = [
        'self-update',
        'list',
    ];

    private $skipOptions = [
        'generate-doc'
    ];

    private $skipArguments = [
    ];

    /**
     * @param ConsoleCommandEvent $event
     * @return void
     */
    public function showGenerateDoc(ConsoleCommandEvent $event)
    {
        /**
         * @var \Drupal\AppConsole\Command\Command $command
         */
        $command = $event->getCommand();
        /**
         * @var \Drupal\AppConsole\Console\Application $command
         */
        $application = $command->getApplication();
        /**
         * @var \Drupal\AppConsole\Config $config
         */
        $config = $application->getConfig();

        $input = $command->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();

        if (isset($options['generate-doc']) && $options['generate-doc'] == 1) {
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

            $renderedDoc = $application->getHelperSet()->get('renderer')->render(
                'base/generate-doc.md.twig',
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
