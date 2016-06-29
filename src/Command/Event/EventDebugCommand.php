<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Event\EventDebugCommand.
 */

namespace Drupal\Console\Command\Event;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\EventsTrait;
use Drupal\Console\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class EventDebugCommand
 * @package Drupal\Console\Command\Event
 */
class EventDebugCommand extends Command
{
    use EventsTrait;
    use ContainerAwareCommandTrait;

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
        
        $event_dispatcher = $this->getDrupalService('event_dispatcher');  
        $events = array_keys($event_dispatcher->getListeners());

        $event = $input->getArgument('event');
        
        if ($event) {

            if (!in_array($event, $events)) {
                throw new \Exception(
                    sprintf(
                        $this->trans('commands.event.debug.messages.no-events'),
                        $module
                    )
                );
            }
            
            $dispacher = $event_dispatcher->getListeners($event);
            $listeners = [];
            
            foreach ($dispacher as $key => $value) {
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
