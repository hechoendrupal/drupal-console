<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ClientCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\ShellProcess;

class ClientCommand extends Command
{
    use ConnectTrait;
    use CommandTrait;

    /** @var ShellProcess  */
    protected $shellProcess;


    /**
     * ClientCommand constructor.
     * @param ShellProcess $shellProcess
     */
    public function __construct(
        ShellProcess $shellProcess
    ) {
        $this->shellProcess = $shellProcess;
        parent::__construct();
    }

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
								null,
								InputOption::VALUE_OPTIONAL,
								$this->trans('commands.database.client.arguments.query')
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
        $query    = $input->getOption('query');
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
                    $this->trans('commands.database.client.messages.connection'),
                    $connection
                )
            );
        }

				if (!$query) {
					$processBuilder = new ProcessBuilder([]);
					$processBuilder->setArguments(explode(' ', $connection));
					$process = $processBuilder->getProcess();
					$process->setTty('true');
					$process->run();

					if (!$process->isSuccessful()) {
						throw new \RuntimeException($process->getErrorOutput());
					}

				}else{
					$shellProcess = $this->shellProcess;
					if ($shellProcess->exec($command)) {
							$io->info(
									sprintf(
											'Query "%s" executed.',
                      $query
                  )
              );
          }
				}

        return 0;
    }
}
