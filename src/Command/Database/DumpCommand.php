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
                $this->trans('commands.database.dump.arguments.database')
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
        $message = $this->getMessageHelper();
        $database = $input->getArgument('database');
        $file = $input->getOption('file');

        $databaseConnection = $this->resolveConnection($message, $database, $output);

        if (!$file) {
            $file = sprintf(
                '%s/%s.sql',
                $this->getSite()->getSitePath(),
                $databaseConnection['database']
            );
        }

        $processBuilder = new ProcessBuilder([]);
        $processBuilder->setArguments(['–lock-all-tables']);
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->setCommandLine(
            sprintf(
                'mysqldump --user=%s --password=%s %s > %s',
                $databaseConnection['username'],
                $databaseConnection['password'],
                $databaseConnection['database'],
                $file
            )
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $message->addDefaultMessage(
            $this->trans('commands.database.dump.messages.success')
        );

        $message->addDefaultMessage(
            $file
        );
    }
}
