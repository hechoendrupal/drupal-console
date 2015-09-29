<?php

/**
 * @file
 * Contains Drupal\Console\Command\EventsTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait EventsTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function eventsQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        $eventCollection = [];
        $output->writeln($this->trans('commands.common.questions.events.message'));

        $events = $this->getEvents();

        while (true) {
            $event = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.common.questions.events.name'), ''),
                function ($event) use ($events) {
                    return $this->validateServiceExist($event, $events);
                },
                false,
                null,
                $events
            );

            if (empty($event)) {
                break;
            }

            $callbackSuggestion = str_replace('.', '_', $event);
            $callback = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.event.subscriber.questions.callback-name'), $callbackSuggestion),
                $callbackSuggestion
            );

            $eventCollection[$event] = $callback;
            $eventKey = array_search($event, $events, true);

            if ($eventKey >= 0) {
                unset($events[$eventKey]);
            }
        }

        return $eventCollection;
    }
}
