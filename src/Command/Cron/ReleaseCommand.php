<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ReleaseCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class ReleaseCommand extends Command
{
    use CommandTrait;

    /** @var \Drupal\Core\Lock\LockBackendInterface  */
    protected $lock;

    /** @var  \Drupal\Console\Utils\ChainQueue */
    protected $chainQueue;

    /**
     * ReleaseCommand constructor.
     * @param \Drupal\Core\Lock\LockBackendInterface    $lock
     * @param \Drupal\Console\Utils\ChainQueue          $chainQueue
     */
    public function __construct(
        \Drupal\Core\Lock\LockBackendInterface $lock,
        \Drupal\Console\Utils\ChainQueue $chainQueue
    ) {
        $this->lock = $lock;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('cron:release')
            ->setDescription($this->trans('commands.cron.release.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        try {
            $this->lock->release('cron');

            $io->info($this->trans('commands.cron.release.messages.released'));
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
        return 0;
    }
}
