<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ClientCommand.
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

class ClientCommand extends Command
{
    use ConnectTrait;
    use CommandTrait;

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
            ->addOption(
                'query',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.query')
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
        $learning = $input->getOption('learning');
        $query    = $input->getOption('query');

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

				$args = explode(' ', $connection);
				if ($query) {
						$args[] = "--execute=$query";
				}

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
