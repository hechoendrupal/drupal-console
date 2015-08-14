<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\MigrateLoadCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class MigrateLoadCommand extends ContainerAwareCommand
{
    protected $file_data;
    protected $migration_id_found = false;

    protected function configure()
    {
        $this
            ->setName('migrate:load')
            ->setDescription($this->trans('commands.migrate.load.description'))
            ->addOption(
                'override',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.load.questions.override')
            )
            ->addArgument('file', InputArgument::OPTIONAL, $this->trans('commands.migrate.load.arguments.file'));

        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception(' You must provide a valid file path and name.');
            }

            return $value;
        };

        $file = $input->getArgument('file');

        if (!$file) {
            $dialog = $this->getDialogHelper();
            $file = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.migrate.load.questions.file'),
                    ''
                ),
                $validator_required,
                false,
                ''
            );
        }

        $input->setArgument('file', $file);

        $this->file_data = $this->loadDataFile($file);
        $this->migration_id_found = $this->validateMigration($this->file_data['migration_groups']['0'], $this->file_data['id']);

        $override = $input->getOption('override');

        if ($this->migration_id_found === true) {
            $override_required = function ($value) {
                if (!strlen(trim($value))) {
                    throw new \Exception(' Please provide an answer.');
                }

                return $value;
            };

            $dialog = $this->getDialogHelper();
            $override = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.migrate.load.questions.override'),
                    ''
                ),
                $override_required,
                false,
                ''
            );
        }
        $input->setOption('override', $override);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getHelperSet()->get('message');

        $file = null;
        if ($input->hasArgument('file')) {
            $file = $input->getArgument('file');
        }

        if (!file_exists($file)) {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.migrate.load.messages.invalid_file'),
                    $file
                )
            );

            return 1;
        }

        if ($this->migration_id_found === false) {
            $migration_entity = $this->generateEntity($this->file_data, 'migration');

            if ($migration_entity->isInstallable()) {
                $migration_entity->trustData()->save();
                $output->writeln('[+] <info>'.sprintf($this->trans('commands.migrate.load.messages.installed').'</info>'));
            }
        }

        $override = $input->getOption('override');

        if ($override === 'yes') {
            $migration_updated = $this->updateEntity($this->file_data['id'], 'migration', $this->file_data);
            $migration_updated->trustData()->save();

            $output->writeln('[+] <info>'.sprintf($this->trans('commands.migrate.load.messages.overridden').'</info>'));
        }
    }

    protected function validateMigration($drupal_version, $migrate_id)
    {
        $migration_id_found = false;
        $migrations = $this->getMigrations($drupal_version);
        foreach ($migrations as $migration_id => $migration) {
            if (strcmp($migration_id, $migrate_id) == 0) {
                $migration_id_found = true;
                break;
            }
        }

        return $migration_id_found;
    }

    protected function loadDataFile($file)
    {
        $yml = new Parser();
        $file_data = $yml->parse(file_get_contents($file));

        return $file_data;
    }
}
