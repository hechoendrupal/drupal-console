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
                $this->trans('commands.database.restore.arguments.database')
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
        $message = $this->getMessageHelper();
        $database = $input->getArgument('database');
        $file = $input->getOption('file');

        $databaseConnection = $this->resolveConnection($message, $database, $output);

        if (!$file) {
            $message->addErrorMessage(
                $this->trans('commands.database.restore.messages.no-file')
            );
            return;
        }

        $processBuilder = new ProcessBuilder([]);
        $processBuilder->setArguments(['--show-warnings']);
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->setCommandLine(
            sprintf(
                'mysql --user=%s --password=%s %s < %s',
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
            $this->trans('commands.database.restore.messages.success')
        );

        $message->addDefaultMessage(
            $file
        );
    }
}
