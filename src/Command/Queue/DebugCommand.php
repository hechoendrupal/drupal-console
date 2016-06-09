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
    $this->listQueues($io);
  }

  /**
   * @param \Drupal\Console\Style\DrupalStyle $io
   */
  private function listQueues(DrupalStyle $io) {
    $header = ['queue', 'items', 'class'];
    $workerManager = \Drupal::service('plugin.manager.queue_worker');
    foreach ($workerManager->getDefinitions() as $name => $info) {
      $q = \Drupal::queue($name);
      $queues[$name] = array_combine(
        $header,
        [$name, $q->numberOfItems(), get_class($q)]
      );
    }
    $io->table($header, $queues);
  }

}
