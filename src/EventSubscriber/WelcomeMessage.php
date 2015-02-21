<?php

/**
 * @file
 * Contains \Drupal\AppConsole\EventSubscriber\WelcomeMessage.
 */

namespace Drupal\AppConsole\EventSubscriber;

use Drupal\AppConsole\Command\Helper\TranslatorHelper;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WelcomeMessage implements EventSubscriberInterface
{

    /**
     * @var TranslatorHelper
     */
    protected $trans;

    /**
     * @param TranslatorHelper $trans
     */
    public function __construct(TranslatorHelper $trans)
    {
        $this->trans = $trans;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function showMessage(ConsoleCommandEvent $event)
    {
        /** @var \Drupal\AppConsole\Command\Command $command */
        $command = $event->getCommand();
        $output = $event->getOutput();

        if (method_exists($command, 'getDependencies')) {
            $dependencies = $command->getDependencies();
            foreach ($dependencies as $dependency) {
                if (\Drupal::moduleHandler()->moduleExists($dependency) === false) {
                    $errorMessage = sprintf(
                        $this->trans->trans('commands.common.errors.module-dependency'),
                        $dependency
                    );
                    $command->showMessage($output, $errorMessage, 'error');
                    $event->disableCommand();
                }
            }
        }

        $welcomeMessageKey = 'commands.' . str_replace(':', '.', $command->getName()) . '.welcome';
        $welcomeMessage = $this->trans->trans($welcomeMessageKey);

        if ($welcomeMessage != $welcomeMessageKey) {
            $command->showMessage($output, $welcomeMessage);
        }

    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showMessage'];
    }


}