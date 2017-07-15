<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Queue\DebugCommand.
 */

namespace Drupal\Console\Command\Queue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class RunCommand
 *
 * @package Drupal\Console\Command\Queue
 */
class RunCommand extends Command
{
    use CommandTrait;

    /**
     * @var QueueWorkerManagerInterface
     */
    protected $queueWorker;


    /**
     * @var QueueFactory
     */
    protected $queue;

    /**
     * DebugCommand constructor.
     *
     * @param QueueWorkerManagerInterface $queueWorker
     * @param QueueFactory                $queue
     */
    public function __construct(
        QueueWorkerManagerInterface $queueWorker,
        QueueFactory $queue
    ) {
        $this->queueWorker = $queueWorker;
        $this->queue = $queue;
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
        $io = new DrupalStyle($input, $output);
        $name = $input->getArgument('name');

        if (!$name) {
            $io->error(
                $this->trans('commands.queue.run.messages.missing-name')
            );

            return 1;
        }

        try {
            $worker = $this->queueWorker->createInstance($name);
        } catch (\Exception $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.queue.run.messages.invalid-name'),
                    $name
                )
            );

            return 1;
        }

        $start = microtime(true);
        $result = $this->runQueue($worker);
        $time = microtime(true) - $start;

        if (!empty($result['error'])) {
            $io->error(
                sprintf(
                    $this->trans('commands.queue.run.messages.failed'),
                    $name,
                    $result['error']
                )
            );

            return 1;
        }

        $io->success(
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
     * @param $worker
     *
     * @return array
     */
    private function runQueue($worker)
    {
        $result['count'] = 0;
        $result['total'] = $this->queue->numberOfItems();
        while ($item = $this->queue->claimItem()) {
            try {
                $worker->processItem($item->data);
                $this->queue->deleteItem($item);
                $result['count']++;
            } catch (SuspendQueueException $e) {
                $this->queue->releaseItem($item);
                $result['error'] = $e;
            }
        }

        return $result;
    }
}
