<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Queue\DebugCommand.
 */

namespace Drupal\Console\Command\Queue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Queue
 */
class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var QueueWorkerManagerInterface
     */
    protected $queueWorker;

    /**
     * DebugCommand constructor.
     *
     * @param QueueWorkerManagerInterface $queueWorker
     */
    public function __construct(QueueWorkerManagerInterface $queueWorker)
    {
        $this->queueWorker = $queueWorker;
        parent::__construct();
    }

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

        return 0;
    }

    /**
     * listQueues.
     */
    private function listQueues()
    {
        $queues = [];
        foreach ($this->queueWorker->getDefinitions() as $name => $info) {
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
