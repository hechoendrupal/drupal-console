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
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Utils\ShellProcess;

class DumpCommand extends Command
{
    use ConnectTrait;


    protected $appRoot;
    /**
     * @var ShellProcess
     */
    protected $shellProcess;

    /**
     * DumpCommand constructor.
     *
     * @param $appRoot
     * @param ShellProcess $shellProcess
     */
    public function __construct(
        $appRoot,
        ShellProcess $shellProcess
    ) {
        $this->appRoot = $appRoot;
        $this->shellProcess = $shellProcess;
        parent::__construct();
    }

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
                $this->trans('commands.database.dump.options.file')
            )
            ->addOption(
                'gz',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.database.dump.options.gz')
            )
            ->setHelp($this->trans('commands.database.dump.help'))
            ->setAliases(['dbdu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getArgument('database');
        $file = $input->getOption('file');
        $learning = $input->getOption('learning');
        $gz = $input->getOption('gz');

        $databaseConnection = $this->resolveConnection($database);

        if (!$file) {
            $date = new \DateTime();
            $file = sprintf(
                '%s/%s-%s.sql',
                $this->appRoot,
                $databaseConnection['database'],
                $date->format('Y-m-d-H-i-s')
            );
        }

        $command = null;

        if ($databaseConnection['driver'] == 'mysql') {
            $command = sprintf(
                'mysqldump --user="%s" --password="%s" --host="%s" --port="%s" "%s" > "%s"',
                $databaseConnection['username'],
                $databaseConnection['password'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database'],
                $file
            );
        } elseif ($databaseConnection['driver'] == 'pgsql') {
            $command = sprintf(
                'PGPASSWORD="%s" pg_dumpall -w -U "%s" -h "%s" -p "%s" -l "%s" -f "%s"',
                $databaseConnection['password'],
                $databaseConnection['username'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database'],
                $file
            );
        }

        if ($learning) {
            $this->getIo()->commentBlock($command);
        }

        if ($this->shellProcess->exec($command, $this->appRoot)) {
            $resultFile = $file;
            if ($gz) {
                if (substr($file, -3) != '.gz') {
                    $resultFile = $file . ".gz";
                }
                file_put_contents(
                    $resultFile,
                    gzencode(
                        file_get_contents(
                            $file
                        )
                    )
                );
                if ($resultFile != $file) {
                    unlink($file);
                }
            }

            $this->getIo()->success(
                sprintf(
                    '%s %s',
                    $this->trans('commands.database.dump.messages.success'),
                    $resultFile
                )
            );
        }

        return 0;
    }
}
