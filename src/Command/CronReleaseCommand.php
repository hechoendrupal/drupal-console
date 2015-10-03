<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RestDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOptionuse;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Lock\LockBackendInterface;

class CronReleaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:release')
            ->setDescription($this->trans('commands.cron.release.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->getDatabaseLockBackend();

        try {

            // Release cron lock.
            $lock->release('cron');

            $output->writeln(
                '[-] <info>' .
                $this->trans('commands.cron.release.messages.released')
                . '</info>'
            );
        } catch (Exception $e) {
            $output->writeln(
                '<error>' .
                $e->getMessage() .
                '</error>'
            );
        }

        $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
