<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ConnectCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class ConnectCommand extends Command
{
    use CommandTrait;
    use ConnectTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:connect')
            ->setDescription($this->trans('commands.database.connect.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.connect.arguments.database'),
                'default'
            )
            ->setHelp($this->trans('commands.database.connect.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $database = $input->getArgument('database');
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

        $io->commentBlock(
            sprintf(
                $this->trans('commands.database.connect.messages.connection'),
                $connection
            )
        );

        return 0;
    }
}
