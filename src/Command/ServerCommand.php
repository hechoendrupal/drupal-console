<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ServerCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('server')
            ->setDescription($this->trans('commands.server.description'))
            ->addArgument(
                'address',
                InputArgument::OPTIONAL,
                $this->trans('commands.server.arguments.address'),
                '127.0.0.1:8088'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $address = $input->getArgument('address');
        if (false === strpos($address, ':')) {
            $address = sprintf(
                '%s:8088',
                $address
            );
        }

        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find()) {
            $io->error($this->trans('commands.server.errors.binary'));
            return;
        }

        $io->success(
            sprintf(
                $this->trans('commands.server.messages.executing'),
                $binary
            )
        );

        $processBuilder = new ProcessBuilder([$binary, '-S', $address]);
        $process = $processBuilder->getProcess();
        $process->setWorkingDirectory($this->getDrupalHelper()->getRoot());
        $process->setTty('true');
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error($process->getErrorOutput());
        }
    }
}
