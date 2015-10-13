<?php

/**
 * @file
 * Contains \Drupal\Console\Command\DrushCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;

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
        $database = $input->getArgument('database');
        if (!$database) {
            $database = 'default';
        }

        $connectionInfo = $this->getConnectionInfo();

        if(!isset($connectionInfo[$database])) {
            $output->writeln('[+] <error>'.
                sprintf(
                    $this->trans('commands.db.client.messages.database-not-found'),
                    $database)
                .'</error>');
        }

        $db = $connectionInfo[$database];
        if( $db['driver'] == 'mysql') {
            $command = sprintf('mysql -u%s -p%s %s -h%s -P%s', $db['username'], $db['password'],$db['database'], $db['host'], $db['port']
                );
            $output->writeln('[+] <info>'. 'Executing:'. $command . '</info>');
            if (`which mysql`) {
                system($command);
            } else {
                $output->writeln('[+] <error>'.
                    sprintf(
                        $this->trans('commands.db.client.messages.database-client-not-found'),
                        'mysql')
                    .'</error>');
            }
        } else {
            $output->writeln('[+] <error>'.
                sprintf(
                    $this->trans('commands.db.client.messages.database-not-supported'),
                    $db['driver'])
                .'</error>');
        }
    }
}
