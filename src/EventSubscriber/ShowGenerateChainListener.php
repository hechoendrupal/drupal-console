<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\ShowGeneratedFiles.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\AppConsole\Command\GeneratorCommand;
use Symfony\Component\Yaml\Dumper;

class ShowGenerateChainListener implements EventSubscriberInterface
{
    private $skipCommands = [
        'self-update',
        'list',
    ];

    private $skipOptions = [
        'env',
        'generate-chain',
    ];

    private $skipArguments = [
    ];
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateChain(ConsoleTerminateEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();
        $command_name = $command->getName();

        $this->skipArguments[] = $command_name;

        $application = $command->getApplication();
        $messageHelper = $application->getHelperSet()->get('message');
        /* @var TranslatorHelper */
        $translatorHelper = $application->getHelperSet()->get('translator');

        $messageHelper->showMessages($output);

        if ($event->getExitCode() != 0) {
            return;
        }

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        $completedMessageKey = 'application.console.messages.completed';

        if ($command instanceof GeneratorCommand) {
            $completedMessageKey = 'application.console.messages.generated.completed';
        }

        //print_r($command->getDefinition()->getArguments());
        //print_r($command->getDefinition()->getOptions());

        // get the input instance
        $input = $event->getInput();

        //Get options list
        $options = array_diff(array_filter($input->getOptions()), $this->skipOptions);

        if (isset($options['generate-chain']) && $options['generate-chain'] == 1) {
            // Get argument list
            $arguments = array_diff(array_filter($input->getArguments()), $this->skipArguments);

            $yaml = array();
            $yaml[$command_name]['options'] = $options;
            $yaml[$command_name]['arguments'] = $arguments;

            $dumper = new Dumper();

            $yaml = $dumper->dump($yaml, 10);

            // Print yaml output and message
            $messageHelper->showMessage($output, $translatorHelper->trans('application.console.messages.chain.generated'));
            print $yaml;
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
