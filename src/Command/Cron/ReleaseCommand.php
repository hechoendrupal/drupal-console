<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ReleaseCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Console\Core\Utils\ChainQueue;

class ReleaseCommand extends Command
{
    /**
     * @var LockBackendInterface
     */
    protected $lock;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * ReleaseCommand constructor.
     *
     * @param LockBackendInterface $lock
     * @param ChainQueue           $chainQueue
     */
    public function __construct(
        LockBackendInterface $lock,
        ChainQueue $chainQueue
    ) {
        $this->lock = $lock;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cron:release')
            ->setDescription($this->trans('commands.cron.release.description'))
            ->setAliases(['cror']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->lock->release('cron');

            $this->getIo()->info($this->trans('commands.cron.release.messages.released'));
        } catch (Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

        return 0;
    }
}
