<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\QueryCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Style\DrupalStyle;

class QueryCommand extends Command
{
    use ConnectTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:query')
            ->setDescription($this->trans('commands.database.query.description'))
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                $this->trans('commands.database.query.arguments.query'),
                ''
            )
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.query.arguments.database'),
                'default'
            )
            ->setHelp($this->trans('commands.database.query.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $database = $input->getArgument('query');
        $database = $input->getArgument('database');
        $learning = $input->getOption('learning');

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
                    $this->trans('commands.database.query.messages.connection'),
                    $connection
                )
            );
        }

				$args = explode(' ', $connection);
				$args[] = "--execute=$query";

        $processBuilder = new ProcessBuilder([]);
        $processBuilder->setArguments($args);
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return 0;
    }
}
