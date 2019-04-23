<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ConnectCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ConnectTrait;

class ConnectCommand extends Command
{
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
                'key',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.connect.arguments.key'),
                'default'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.connect.arguments.target'),
                'default'
            )
            ->setHelp($this->trans('commands.database.connect.help'))
            ->setAliases(['dbco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $target = $input->getArgument('target');
        $databaseConnection = $this->resolveConnection($key, $target);

        $this->getIo()->commentBlock(
            sprintf(
                $this->trans('commands.database.connect.messages.connection'),
                escapeshellcmd($this->getConnectionString($databaseConnection))
            )
        );

        return 0;
    }
}
