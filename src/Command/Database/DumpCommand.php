<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\DumpCommand.
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

class DumpCommand extends ContainerAwareCommand
{
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:dump')
            ->setDescription($this->trans('commands.database.dump.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.dump.arguments.database'),
                'default'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.dump.option.file')
            )
            ->setHelp($this->trans('commands.database.dump.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $database = $input->getArgument('database');
        $file = $input->getOption('file');
        $learning = $input->getOption('learning');

        $databaseConnection = $this->resolveConnection($io, $database);

        if (!$file) {
            $file = sprintf(
                '%s/%s.sql',
                $this->getSite()->getSiteRoot(),
                $databaseConnection['database']
            );
        }

        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
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

        $processBuilder = new ProcessBuilder(['–lock-all-tables']);
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->setCommandLine($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $io->success(
            sprintf(
                '%s %s',
                $this->trans('commands.database.dump.messages.success'),
                $file
            )
        );
    }
}
