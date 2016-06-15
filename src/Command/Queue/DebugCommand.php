<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Queue\DebugCommand.
 */

namespace Drupal\Console\Command\Queue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Queue
 */
class DebugCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('queue:debug')
            ->setDescription($this->trans('commands.queue.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $tableHeader = [
            $this->trans('commands.queue.debug.messages.queue'),
            $this->trans('commands.queue.debug.messages.items'),
            $this->trans('commands.queue.debug.messages.class')
        ];

        $tableBody = $this->listQueues();

        $io->table($tableHeader, $tableBody);
    }

    /**
     * listQueues.
     */
    private function listQueues()
    {
        $queueManager = $this->getDrupalService('plugin.manager.queue_worker');
        $queues = [];
        foreach ($queueManager->getDefinitions() as $name => $info) {
            $queues[$name] = $this->formatQueue($name);
        }

        return $queues;
    }

    /**
     * @param $name
     * @return array
     */
    private function formatQueue($name)
    {
        $q = $this->getDrupalService('queue')->get($name);

        return [
            $name,
            $q->numberOfItems(),
            get_class($q)
        ];
    }
}
