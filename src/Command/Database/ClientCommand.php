<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ClientCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Style\DrupalStyle;

class ClientCommand extends Command
{
    use ConnectTrait;
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:client')
            ->setDescription($this->trans('commands.database.client.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.client.arguments.database'),
                'default'
            )
            ->setHelp($this->trans('commands.database.client.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $database = $input->getArgument('database');
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $databaseConnection = $this->resolveConnection($io, $database);

        $connection = sprintf(
            '%s -A --database=%s --user=%s --password=%s --host=%s --port=%s',
            $databaseConnection['driver'],
            $databaseConnection['database'],
            $databaseConnection['username'],
            $databaseConnection['password'],
            $databaseConnection['host'],
            $databaseConnection['port']
        );

        if ($learning) {
            $io->commentBlock(
                sprintf(
                    $this->trans('commands.database.client.messages.connection'),
                    $connection
                )
            );
        }

        $processBuilder = new ProcessBuilder([]);
        $processBuilder->setArguments(explode(' ', $connection));
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}
