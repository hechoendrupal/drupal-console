<?php

/**
 * @file
 * Contains \Drupal\Console\Command\EventCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class EventCommand
 *
 *  @package Drupal\Console\Command\Debug
 */
class EventCommand extends Command
{
    use CommandTrait;

    protected $eventDispatcher;

    /**
     * EventDebugCommand constructor.
     *
     * @param $eventDispatcher
     */
    public function __construct($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:event')
            ->setDescription($this->trans('commands.debug.event.description'))
            ->addArgument(
                'event',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.event.arguments.event'),
                null
            )
            ->setHelp($this->trans('commands.debug.event.blerp'))
            ->setAliases(['dev']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $events = array_keys($this->eventDispatcher->getListeners());
        $event = $input->getArgument('event');

        if ($event) {
            if (!in_array($event, $events)) {
                throw new \Exception(
                    sprintf(
                        $this->trans('commands.debug.event.messages.no-events'),
                        $event
                    )
                );
            }

            $dispatcher = $this->eventDispatcher->getListeners($event);
            $listeners = [];

            foreach ($dispatcher as $key => $value) {
                $reflection = new \ReflectionClass(get_class($value[0]));
                $className = $reflection->getName();

                if (!$reflection->hasMethod('getSubscribedEvents')) {
                    $reflection = new \ReflectionClass($reflection->getParentClass());
                }

                $eventObject = $reflection->newInstanceWithoutConstructor();
                $reflectionMethod = new \ReflectionMethod(
                    $reflection->getName(),
                    'getSubscribedEvents'
                );

                $subscribedEvents = $reflectionMethod->invoke(
                    $eventObject
                );

                if (!is_array($subscribedEvents[$event])) {
                    $subscribedEvents[$event] = [$subscribedEvents[$event]];
                }

                $subscribedEventData = [];
                foreach ($subscribedEvents[$event] as $subscribedEvent) {
                    if (!is_array($subscribedEvent)) {
                        $subscribedEvent = [$subscribedEvent, 0];
                    }
                    if ($subscribedEvent[0] == $value[1]) {
                        $subscribedEventData = [
                            $subscribedEvent[0] => isset($subscribedEvent[1])?$subscribedEvent[1]:0
                        ];
                    }
                }

                $listeners[] = [
                    'class' => $className,
                    'method' => $value[1],
                    'events' => Yaml::dump($subscribedEventData, 4, 2)
                ];
            }

            $tableHeader = [
               $this->trans('commands.debug.event.messages.class'),
               $this->trans('commands.debug.event.messages.method'),
            ];

            $tableRows = [];
            foreach ($listeners as $key => $element) {
                $tableRows[] = [
                    'class' => $element['class'],
                    'events' => $element['events']
                 ];
            }

            $io->table($tableHeader, $tableRows);

            return 0;
        }

        $io->table(
            [$this->trans('commands.debug.event.messages.event')],
            $events
        );
    }
}
