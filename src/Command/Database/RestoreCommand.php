<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\RestoreCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\Database\ConnectTrait;
use Drupal\Console\Style\DrupalStyle;

class RestoreCommand extends ContainerAwareCommand
{
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:restore')
            ->setDescription($this->trans('commands.database.restore.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.restore.arguments.database'),
                'default'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.database.restore.options.file')
            )
            ->setHelp($this->trans('commands.database.restore.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $database = $input->getArgument('database');
        $learning = $input->getOption('learning');
        $file = $input->getOption('file');

        $databaseConnection = $this->resolveConnection($io, $database);

        if (!$file) {
            $io->error(
                $this->trans('commands.database.restore.messages.no-file')
            );
            return;
        }

        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s %s < %s',
            $databaseConnection['username'],
            $databaseConnection['password'],
            $databaseConnection['host'],
            $databaseConnection['port'],
            $databaseConnection['database'],
            $file
        );

        if ($learning) {
            $io->commentBlock($command);
        }

        $processBuilder = new ProcessBuilder(['-v']);
        $process = $processBuilder->getProcess();
        $process->setWorkingDirectory($this->getDrupalHelper()->getRoot());
        $process->setTty('true');
        $process->setCommandLine($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $io->success(
            sprintf(
                '%s %s',
                $this->trans('commands.database.restore.messages.success'),
                $file
            )
        );
    }
}
