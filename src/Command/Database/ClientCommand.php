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
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ConnectTrait;

class ClientCommand extends Command
{
    use ConnectTrait;

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
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.client.arguments.target'),
                'default'
            )
            ->setHelp($this->trans('commands.database.client.help'))
            ->setAliases(['dbc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getArgument('database');
        $learning = $input->getOption('learning');
        $target = $input->getArgument('target');

        $databaseConnection = $this->resolveConnection($database, $target);
        $connection = $this->getConnectionString($databaseConnection);

        if ($learning) {
            $this->getIo()->commentBlock(
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

        return 0;
    }
}
