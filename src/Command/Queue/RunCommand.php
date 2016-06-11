<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Queue\DebugCommand.
 */

namespace Drupal\Console\Command\Queue;

use MongoDB\BSON\Timestamp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class RunCommand
 * @package Drupal\Console\Command\Queue
 */
class RunCommand extends Command
{
  use ContainerAwareCommandTrait;

  /**
   * @var $queueManager \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  private $queueManager;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('queue:run')
      ->setDescription($this->trans('commands.queue.run.description'))
      ->addArgument(
        'queue-name',
        InputArgument::REQUIRED,
        $this->trans('commands.queue.run.arguments.queue-name')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new DrupalStyle($input, $output);
    $queue_name = $input->getArgument('queue-name');
    if ($queue_name) {
      $this->queueManager = $this->getDrupalService('plugin.manager.queue_worker');
      $this->runQueue($io, $queue_name);
    }

  }

  /**
   * @param \Drupal\Console\Style\DrupalStyle $io
   * @param $queue_name
   */
  private function runQueue(DrupalStyle $io, $queue_name) {
    $worker = $this->queueManager->createInstance($queue_name);
    $q = $this->getDrupalService('queue')->get($queue_name);
    $start = microtime(true);
    $result = $this->clearQueue($worker, $q);
    $time = microtime(true) - $start;
    if (empty($result['error'])) {
      $io->success(
        sprintf(
          $this->trans('commands.queue.run.success'),
          $queue_name,
          $result['count'],
          $result['total'],
          round($time, 2)
        )
      );
    } else {
      $io->error(
        sprintf(
          $this->trans('commands.queue.run.error'),
          $queue_name,
          $result['error']
        )
      );
    }
  }

  /**
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $worker
   * @param \Drupal\Core\Queue\Queue $q
   * @return array
   */
  private function clearQueue($worker, $q) {
    $result['count'] = 0;
    $result['total'] = $q->numberOfItems();
    while ($item = $q->claimItem()) {
      try {
        $worker->processItem($item->data);
        $q->deleteItem($item);
        $result['count']++;
      }
      catch (SuspendQueueException $e) {
        $q->releaseItem($item);
        $result['error'] = $e;
      }

    }

    return $result;
  }

}
