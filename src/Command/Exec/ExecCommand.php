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
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\ShellProcess;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ExecCommand
 * @package Drupal\Console\Command\Exec
 */
class ExecCommand extends Command
{
    use CommandTrait;

    /**
     * @var ShellProcess
     */
    protected $shellProcess;

    /**
     * ExecCommand constructor.
     * @param ShellProcess $shellProcess
     */
    public function __construct(ShellProcess $shellProcess)
    {
        $this->shellProcess = $shellProcess;
        parent::__construct();
    }

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

        if (!$this->shellProcess->exec($bin)) {
            $io->error(
                sprintf(
                    $this->trans('commands.exec.messages.invalid-bin')
                )
            );

            $io->writeln($this->shellProcess->getOutput());

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.exec.messages.success'),
                $bin
            )
        );

        return 0;
    }
}
