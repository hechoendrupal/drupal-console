<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\EventsTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class EventsTrait
 *
 * @package Drupal\Console\Command
 */
trait EventsTrait
{
    /**
     * @return mixed
     */
    public function eventsQuestion()
    {
        $eventCollection = [];
        $this->getIo()->info($this->trans('commands.common.questions.events.message'));

        $events = $this->getEvents();

        while (true) {
            $event = $this->getIo()->choiceNoList(
                $this->trans('commands.common.questions.events.name'),
                $events,
                '',
                true
            );

            if (empty($event) || is_numeric($event)) {
                break;
            }

            $callbackSuggestion = str_replace('.', '_', $event);

            $callback = $this->getIo()->ask(
                $this->trans('commands.generate.event.subscriber.questions.callback-name'),
                $this->stringConverter->underscoreToCamelCase($callbackSuggestion)
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
