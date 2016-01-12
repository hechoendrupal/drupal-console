<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ReleaseCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class ReleaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:release')
            ->setDescription($this->trans('commands.cron.release.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $lock = $this->getDatabaseLockBackend();

        try {
            $lock->release('cron');

            $io->info($this->trans('commands.cron.release.messages.released'));
        } catch (Exception $e) {
            $io->error($e->getMessage());
        }

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
