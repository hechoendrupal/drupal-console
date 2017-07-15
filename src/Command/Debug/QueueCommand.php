<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\QueueCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class QueueCommand extends Command
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
            ->setName('debug:queue')
            ->setDescription($this->trans('commands.debug.queue.description'))
            ->setAliases(['dq']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $tableHeader = [
            $this->trans('commands.debug.queue.messages.queue'),
            $this->trans('commands.debug.queue.messages.items'),
            $this->trans('commands.debug.queue.messages.class')
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
