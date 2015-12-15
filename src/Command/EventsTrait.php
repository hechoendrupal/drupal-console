<?php

/**
 * @file
 * Contains Drupal\Console\Command\EventsTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

trait EventsTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function eventsQuestion(DrupalStyle $output)
    {
        $eventCollection = [];
        $output->writeln($this->trans('commands.common.questions.events.message'));

        $events = $this->getEvents();

        while (true) {
            $event = $output->choiceNoList(
                $this->trans('commands.common.questions.events.name'),
                $events,
                null,
                true
            );

            if (empty($event)) {
                break;
            }

            $callbackSuggestion = str_replace('.', '_', $event);
            $callback = $output->ask(
                $this->trans('commands.generate.event.subscriber.questions.callback-name'),
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
