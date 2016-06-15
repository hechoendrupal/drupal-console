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
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class RunCommand
 * @package Drupal\Console\Command\Queue
 */
class RunCommand extends Command
{
    use ContainerAwareCommandTrait;

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
            );
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

        $queueManager = $this->getDrupalService('plugin.manager.queue_worker');

        try {
            $worker = $queueManager->createInstance($name);
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
        $result = $this->runQueue($worker, $name);
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
    }

    /**
     * @param $worker
     * @param $name
     *
     * @return array
     */
    private function runQueue($worker, $name)
    {
        $q = $this->getDrupalService('queue')->get($name);
        $result['count'] = 0;
        $result['total'] = $q->numberOfItems();
        while ($item = $q->claimItem()) {
            try {
                $worker->processItem($item->data);
                $q->deleteItem($item);
                $result['count']++;
            } catch (SuspendQueueException $e) {
                $q->releaseItem($item);
                $result['error'] = $e;
            }
        }

        return $result;
    }
}
