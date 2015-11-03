<?php

/**
 * @file
 * Contains \Drupal\Console\Command\CronReleaseCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOptionuse;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;

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
            $lock->release('cron');

            $output->writeln(
                sprintf(
                    '[-] <info>%s</info>',
                    $this->trans('commands.cron.release.messages.released')
                )
            );
        } catch (Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    $e->getMessage()
                )
            );
        }

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
