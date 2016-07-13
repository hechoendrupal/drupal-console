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
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Style\DrupalStyle;

class DumpCommand extends Command
{
    use ContainerAwareCommandTrait;
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
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $databaseConnection = $this->resolveConnection($io, $database);

        if (!$file) {
            $date = new \DateTime();
            $siteRoot = rtrim($this->getApplication()->getSite()->getSiteRoot(), '/');
            $file = sprintf(
                '%s/%s-%s.sql',
                $siteRoot,
                $databaseConnection['database'],
                $date->format('Y-m-d-h-i-s')
            );
        }

        $command = null;

        if ($databaseConnection['driver'] == 'mysql') {
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
                $databaseConnection['username'],
                $databaseConnection['password'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database'],
                $file
            );
        } elseif ($databaseConnection['driver'] == 'pgsql') {
            $command = sprintf(
                'PGPASSWORD="%s" pg_dumpall -w -U %s -h %s -p %s -l %s -f %s',
                $databaseConnection['password'],
                $databaseConnection['username'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database'],
                $file
            );
        }

        if ($learning) {
            $io->commentBlock(
                str_replace(
                    $databaseConnection['password'],
                    str_repeat("*", strlen($databaseConnection['password'])),
                    $command
                )
            );
        }

        $shellProcess = $this->get('shell_process');
        if ($shellProcess->exec($command, true)) {
            $io->success(
                sprintf(
                    '%s %s',
                    $this->trans('commands.database.dump.messages.success'),
                    $file
                )
            );
        }
    }
}
