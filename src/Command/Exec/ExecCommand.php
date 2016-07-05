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

        $shellProcess = $this->get('shell_process');
        if ($shellProcess->exec($bin, true)) {
            $io->success(
                sprintf(
                    $this->trans('commands.exec.messages.success'),
                    $bin
                )
            );
        } else {
            $io->error(
                sprintf(
                    $this->trans('commands.exec.messages.invalid-bin')
                )
            );
            return 1;
        }
    }
}
