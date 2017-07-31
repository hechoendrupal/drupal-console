<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ConnectCommand.
 */

namespace Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Generator\DatabaseSettingsGenerator;
use Drupal\Console\Command\Shared\ConnectTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class AddCommand extends Command
{
    use ConnectTrait;

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
                InputOption::VALUE_REQUIRED,
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
            ->setHelp($this->trans('commands.database.add.help'))
            ->setAliases(['dba']);
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $result = $this
            ->generator
            ->generate($input->getOptions());
        if (!$result) {
            $io->error($this->trans('commands.database.add.error'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $database = $input->getOption('database');
        if (!$database) {
            $database = $io->ask(
                $this->trans('commands.database.add.questions.database'),
                'migrate_db'
            );
        }
        $input->setOption('database', $database);
        $username = $input->getOption('username');
        if (!$username) {
            $username = $io->ask(
                $this->trans('commands.database.add.questions.username'),
                ''
            );
        }
        $input->setOption('username', $username);
        $password = $input->getOption('password');
        if (!$password) {
            $password = $io->ask(
                $this->trans('commands.database.add.questions.password'),
                ''
            );
        }
        $input->setOption('password', $password);
        $prefix = $input->getOption('prefix');
        if (!$prefix) {
            $prefix = $io->ask(
                $this->trans('commands.database.add.questions.prefix'),
                false
            );
        }
        $input->setOption('prefix', $prefix);
        $host = $input->getOption('host');
        if (!$host) {
            $host = $io->ask(
                $this->trans('commands.database.add.questions.host'),
                'localhost'
            );
        }
        $input->setOption('host', $host);
        $port = $input->getOption('port');
        if (!$port) {
            $port = $io->ask(
                $this->trans('commands.database.add.questions.port'),
                3306
            );
        }
        $input->setOption('port', $port);
        $driver = $input->getOption('driver');
        if (!$driver) {
            $driver = $io->ask(
                $this->trans('commands.database.add.questions.driver'),
                'mysql'
            );
        }
        $input->setOption('driver', $driver);
    }
}
