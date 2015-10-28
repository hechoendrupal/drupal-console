<?php

/**
 * @file
 * Contains \Drupal\Console\Command\DrushCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class DBClientCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('db:client')
            ->setDescription($this->trans('commands.db.client.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.db.client.arguments.database')
            )
            ->setHelp($this->trans('commands.drush.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getMessageHelper();
        $database = $input->getArgument('database');

        if (!$database) {
            $database = 'default';
        }

        $connectionInfo = $this->getConnectionInfo();

        if (!$connectionInfo || !isset($connectionInfo[$database])) {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.db.client.messages.database-not-found'),
                    $database
                )
            );
            return;
        }

        $db = $connectionInfo[$database];
        if ($db['driver'] !== 'mysql') {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.db.client.messages.database-not-supported'),
                    $db['driver']
                )
            );
            return;
        }

        if (!`which mysql`) {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.db.client.messages.database-client-not-found'),
                    'mysql'
                )
            );
        }

        $command = sprintf(
            '%s -A --database=%s --user=%s --password=%s --host=%s --port=%s',
            $db['driver'],
            $db['database'],
            $db['username'],
            $db['password'],
            $db['host'],
            $db['port']
        );

        $message->showMessage(
            $output,
            sprintf(
                $this->trans('commands.db.client.messages.executing'),
                $command
            )
        );

        $processBuilder = new ProcessBuilder([]);
        $processBuilder->setArguments(explode(' ', $command));
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}
