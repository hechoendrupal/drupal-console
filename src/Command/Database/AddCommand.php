<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ConnectCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Generator\DatabaseSettingsGenerator;

class AddCommand extends Command
{

    /**
     * @var DatabaseSettingsGenerator
     */
    protected $generator;

    /**
     * FormCommand constructor.
     *
     * @param DatabaseSettingsGenerator $generator
     */
    public function __construct(
        DatabaseSettingsGenerator $generator
    ) {
        $this->generator = $generator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:add')
            ->setDescription($this->trans('commands.database.add.description'))
            ->addOption(
                'database',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.database.add.options.database')
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.database.add.options.username')
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.add.options.password')
            )
            ->addOption(
                'prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.add.options.prefix')
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.add.options.host')
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.add.options.port')
            )
            ->addOption(
                'driver',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.database.add.options.driver')
            )
            ->addOption(
                'default',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.database.query.options.default')
            )
            ->setHelp($this->trans('commands.database.add.help'))
            ->setAliases(['dba']);
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this
            ->generator
            ->generate($input->getOptions());
        if (!$result) {
            $this->getIo()->error($this->trans('commands.database.add.error'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getOption('database');
        if (!$database) {
            $database = $this->getIo()->ask(
                $this->trans('commands.database.add.questions.database'),
                'migrate_db'
            );
        }
        $input->setOption('database', $database);
        $username = $input->getOption('username');
        if (!$username) {
            $username = $this->getIo()->ask(
                $this->trans('commands.database.add.questions.username'),
                ''
            );
        }
        $input->setOption('username', $username);
        $password = $input->getOption('password');
        if (!$password) {
            $password = $this->getIo()->askHiddenEmpty(
                $this->trans('commands.migrate.execute.questions.db-pass')
            );
        }
        $input->setOption('password', $password);
        $prefix = $input->getOption('prefix');
        if (!$prefix) {
            $prefix = $this->getIo()->ask(
                $this->trans('commands.database.add.questions.prefix'),
                false
            );
        }
        $input->setOption('prefix', $prefix);
        $host = $input->getOption('host');
        if (!$host) {
            $host = $this->getIo()->ask(
                $this->trans('commands.database.add.questions.host'),
                'localhost'
            );
        }
        $input->setOption('host', $host);
        $port = $input->getOption('port');
        if (!$port) {
            $port = $this->getIo()->ask(
                $this->trans('commands.database.add.questions.port'),
                3306
            );
        }
        $input->setOption('port', $port);
        $driver = $input->getOption('driver');
        if (!$driver) {
            $driver = $this->getIo()->ask(
                $this->trans('commands.database.add.questions.driver'),
                'mysql'
            );
        }
        $input->setOption('driver', $driver);
    }
}
