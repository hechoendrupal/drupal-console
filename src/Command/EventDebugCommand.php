<?php

/**
 * @file
 * Contains \Drupal\Console\Command\EventDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class EventDebugCommand
 *
 *  @package Drupal\Console\Command
 */
class EventDebugCommand extends Command
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
            ->setName('event:debug')
            ->setDescription($this->trans('commands.event.debug.description'))
            ->addArgument(
                'event',
                InputArgument::OPTIONAL,
                $this->trans('commands.event.debug.arguments.event'),
                null
            )
            ->setHelp($this->trans('commands.event.debug.help'));
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
                        $this->trans('commands.event.debug.messages.no-events'),
                        $event
                    )
                );
            }
            
            $dispatcher = $this->eventDispatcher->getListeners($event);
            $listeners = [];
            
            foreach ($dispatcher as $key => $value) {
                $reflection = new \ReflectionClass(get_class($value[0]));
                $listeners[] = [$reflection->getName(), $value[1]];
            }
 
            $tableHeader = [
               $this->trans('commands.event.debug.messages.class'),
               $this->trans('commands.event.debug.messages.method'),

            ];

            $tableRows = [];
            foreach ($listeners as $key => $element) {
                $tableRows[] = [
                    'class' => $element['0'],
                    'method' => $element['1']
                 ];
            }

            $io->table($tableHeader, $tableRows);

            return 0;
        }
       
        $io->table(
            [$this->trans('commands.event.debug.messages.event')],
            $events
        );
    }
}
