<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Queue\DebugCommand.
 */

namespace Drupal\Console\Command\Queue;

use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Queue
 */
class DebugCommand extends Command
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
      ->setName('queue:debug')
      ->setDescription($this->trans('commands.queue.debug.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new DrupalStyle($input, $output);
    $this->queueManager = $this->getDrupalService('plugin.manager.queue_worker');
    $this->listQueues($io);
  }

  /**
   * @param \Drupal\Console\Style\DrupalStyle $io
   */
  private function listQueues(DrupalStyle $io) {
    $header = ['queue', 'items', 'class'];
    foreach ($this->queueManager->getDefinitions() as $name => $info) {
      $queues[$name] = $this->formatQueues($name, $header);
    }
    $io->table($header, $queues);
  }

  /**
   * @param $name
   * @param array $header
   * @return array
   */
  private function formatQueues($name, $header) {
    $q = $this->getDrupalService('queue')->get($name);
    return array_combine($header, [$name, $q->numberOfItems(), get_class($q)]);
  }

}
