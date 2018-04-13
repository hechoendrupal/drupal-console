<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Queue\DebugCommand.
 */

namespace Drupal\Console\Command\Queue;

use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class RunCommand
 *
 * @package Drupal\Console\Command\Queue
 */
class RunCommand extends Command
{
    /**
     * @var QueueWorkerManagerInterface
     */
    protected $queueWorker;


    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * DebugCommand constructor.
     *
     * @param QueueWorkerManagerInterface $queueWorker
     * @param QueueFactory                $queue
     */
    public function __construct(
        QueueWorkerManagerInterface $queueWorker,
        QueueFactory $queueFactory
    ) {
        $this->queueWorker = $queueWorker;
        $this->queueFactory = $queueFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('queue:run')
            ->setDescription($this->trans('commands.queue.run.description'))
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                $this->trans('commands.queue.run.arguments.name')
            )->setAliases(['qr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        if (!$name) {
            $this->getIo()->error(
                $this->trans('commands.queue.run.messages.missing-name')
            );

            return 1;
        }

        try {
            $worker = $this->queueWorker->createInstance($name);
            $queue = $this->queueFactory->get($name);
        } catch (\Exception $e) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.queue.run.messages.invalid-name'),
                    $name
                )
            );

            return 1;
        }

        $start = microtime(true);
        $result = $this->runQueue($queue, $worker);
        $time = microtime(true) - $start;

        if (!empty($result['error'])) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.queue.run.messages.failed'),
                    $name,
                    $result['error']
                )
            );

            return 1;
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.queue.run.success'),
                $name,
                $result['count'],
                $result['total'],
                round($time, 2)
            )
        );

        return 0;
    }

    /**
     * @param \Drupal\Core\Queue\QueueInterface       $queue
     * @param \Drupal\Core\Queue\QueueWorkerInterface $worker
     *
     * @return array
     */
    private function runQueue($queue, $worker)
    {
        $result['count'] = 0;
        $result['total'] = $queue->numberOfItems();
        while ($item = $queue->claimItem()) {
            try {
                $worker->processItem($item->data);
                $queue->deleteItem($item);
                $result['count']++;
            } catch (SuspendQueueException $e) {
                $queue->releaseItem($item);
                $result['error'] = $e;
            }
        }

        return $result;
    }
}
