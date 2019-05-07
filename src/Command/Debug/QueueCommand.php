<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\QueueCommand.
 */

namespace Drupal\Console\Command\Debug;

use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class QueueCommand extends Command
{
    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var QueueWorkerManagerInterface
     */
    protected $queueWorker;

    /**
     * DebugCommand constructor.
     *
     * @param QueueWorkerManagerInterface $queueWorker
     */
    public function __construct(QueueFactory $queueFactory, QueueWorkerManagerInterface $queueWorker)
    {
        $this->queueFactory = $queueFactory;
        $this->queueWorker = $queueWorker;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:queue')
            ->setDescription($this->trans('commands.debug.queue.description'))
            ->setAliases(['dq']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableHeader = [
            $this->trans('commands.debug.queue.messages.queue'),
            $this->trans('commands.debug.queue.messages.items'),
            $this->trans('commands.debug.queue.messages.class')
        ];

        $tableBody = $this->listQueues();

        $this->getIo()->table($tableHeader, $tableBody);

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
        $q = $this->queueFactory->get($name);

        return [
            $name,
            $q->numberOfItems(),
            get_class($q)
        ];
    }
}
