<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\EventsTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class EventsTrait
 *
 * @package Drupal\Console\Command
 */
trait EventsTrait
{
    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function eventsQuestion(DrupalStyle $io)
    {
        $eventCollection = [];
        $io->info($this->trans('commands.common.questions.events.message'));

        $events = $this->getEvents();

        while (true) {
            $event = $io->choiceNoList(
                $this->trans('commands.common.questions.events.name'),
                $events,
                null,
                true
            );

            if (empty($event)) {
                break;
            }

            $callbackSuggestion = str_replace('.', '_', $event);
            $callback = $io->ask(
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

    public function getEvents()
    {
        if (null === $this->events) {
            $this->events = [];
            $this->events = array_keys($this->eventDispatcher->getListeners());
        }
        return $this->events;
    }
}
