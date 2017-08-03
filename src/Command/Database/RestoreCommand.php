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
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class RestoreCommand extends Command
{
    use ConnectTrait;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * RestoreCommand constructor.
     *
     * @param string $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
        parent::__construct();
    }

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
            ->setHelp($this->trans('commands.database.restore.help'))
            ->setAliases(['dbr']);
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
            $io->error(
                $this->trans('commands.database.restore.messages.no-file')
            );
            return 1;
        }
        if ($databaseConnection['driver'] == 'mysql') {
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%s %s < %s',
                $databaseConnection['username'],
                $databaseConnection['password'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database'],
                $file
            );
        } elseif ($databaseConnection['driver'] == 'pgsql') {
            $command = sprintf(
                'PGPASSWORD="%s" psql -w -U %s -h %s -p %s -d %s -f %s',
                $databaseConnection['password'],
                $databaseConnection['username'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database'],
                $file
            );
        }

        if ($learning) {
            $io->commentBlock($command);
        }

        $processBuilder = new ProcessBuilder(['-v']);
        $process = $processBuilder->getProcess();
        $process->setWorkingDirectory($this->appRoot);
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

        return 0;
    }
}
