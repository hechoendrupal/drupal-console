<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Exec\ExecCommand.
 */

namespace Drupal\Console\Command\Exec;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Process\Process;

/**
 * Class ExecCommand
 * @package Drupal\Console\Command\Exec
 */
class ExecCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('exec')
            ->setDescription($this->trans('commands.exec.description'))
            ->addArgument(
                'bin',
                InputArgument::REQUIRED,
                $this->trans('commands.exec.arguments.bin')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $bin = $input->getArgument('bin');

        if (!$bin) {
            $io->error(
                $this->trans('commands.exec.messages.missing-bin')
            );
            return 1;
        }

        $process = new Process($bin);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error(
              sprintf(
                $this->trans('commands.exec.messages.invalid-bin')
              )
            );
            return 1;
        }

        $msg = $process->getOutput();

        $io->info($msg, FALSE);
        $io->success(
          sprintf(
            $this->trans('commands.exec.messages.success'),
            $bin
          )
        );

    }

}
