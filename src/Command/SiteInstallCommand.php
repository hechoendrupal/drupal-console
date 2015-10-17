<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\MigrateExecuteCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SiteInstallCommand extends Command
{
    protected $connection;

    protected function configure()
    {
        $this
            ->setName('site:install')
            ->setDescription($this->trans('commands.site.install.description'))
            ->addArgument('profile', InputArgument::OPTIONAL, $this->trans('commands.site.install.arguments.profile'))
            ->addOption(
                'langcode',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.langcode')
            )
            ->addOption(
                'db-type',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.db-type')
            )
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-user')
            )
            ->addOption(
                'db-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-prefix')
            )
            ->addOption(
                'db-port',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-port')
            )
            ->addOption(
                'site-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.site-name')
            )
            ->addOption(
                'site-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.site-mail')
            )
            ->addOption(
                'account-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.account-name')
            )
            ->addOption(
                'account-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.account-mail')
            )
            ->addOption(
                'account-pass',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.account-pass')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception('The option can not be empty');
            }

            return $value;
        };

        $dialog = $this->getDialogHelper();
        $questionHelper = $this->getQuestionHelper();

        $profiles = $this->getProfiles();

        // <profile> option
        $profile = $input->getArgument('profile');
        if (!$profile) {
            $profile = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    $this->trans('commands.site.install.questions.profile'),
                    array_combine(array_values($profiles), array_values($profiles)),
                    1
                )
            );
        }

        $input->setArgument('profile', array_search($profile, $profiles));

        // --langcode option
        $langcode = $input->getOption('langcode');
        if (!$langcode) {
            $languages = $this->getLanguages();
            $defaultLanguage = $this->getDefaultLanguage();
            $langcode = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.site.install.questions.langcode'), $languages[$defaultLanguage]),
                $validator_required,
                false,
                $languages[$defaultLanguage],
                $languages
            );
        }

        $input->setOption('langcode', array_search($langcode, $languages));

        // --db-type option
        $db_type = $input->getOption('db-type');
        if (!$db_type) {
            $databases = $this->getDatabaseTypes();
            $db_type = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    $this->trans('commands.site.install.questions.db-type'),
                    array_combine(array_column($databases, 'name'), array_column($databases, 'name')),
                    current(array_column($databases, 'name'))
                )
            );
        }
        // find current database type selected to set the proper driver id
        foreach ($databases as $db_index => $database) {
            if ($database['name'] == $db_type) {
                $db_type = $db_index;
            }
        }
        $input->setOption('db-type', $db_type);

        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-host'), '127.0.0.1'),
                $validator_required,
                false,
                '127.0.0.1'
            );
        }
        $input->setOption('db-host', $db_host);

        // --db-name option
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-name'), ''),
                $validator_required,
                false,
                null
            );
        }
        $input->setOption('db-name', $db_name);

        // --db-user option
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-user'), ''),
                $validator_required,
                false,
                null
            );
        }
        $input->setOption('db-user', $db_user);

        // --db-pass option
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $dialog->askHiddenResponse(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-pass'), ''),
                ''
            );
        }
        $input->setOption('db-pass', $db_pass);

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-prefix'), ''),
                ''
            );
        }
        $input->setOption('db-prefix', $db_prefix);

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.migrate.execute.questions.db-port'), '3306'),
                $validator_required,
                false,
                '3306'
            );
        }
        $input->setOption('db-port', $db_port);

        // --site-name option
        $site_name = $input->getOption('site-name');
        if (!$site_name) {
            $site_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.site.install.questions.site-name'), 'Drupal 8 Site Install'),
                $validator_required,
                false,
                'Drupal 8 Site Install'
            );
        }
        $input->setOption('site-name', $site_name);

        // --site-mail option
        $site_mail = $input->getOption('site-mail');
        if (!$site_mail) {
            $site_mail = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.site.install.questions.site-mail'), 'admin@example.com'),
                $validator_required,
                false,
                'admin@example.com'
            );
        }
        $input->setOption('site-mail', $site_mail);

        // --account-name option
        $account_name = $input->getOption('account-name');
        if (!$account_name) {
            $account_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.site.install.questions.account-name'), 'admin'),
                $validator_required,
                false,
                'admin'
            );
        }
        $input->setOption('account-name', $account_name);

        // --account-mail option
        $account_mail = $input->getOption('account-mail');
        if (!$account_mail) {
            $account_mail = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.site.install.questions.account-mail'), 'admin@example.com'),
                $validator_required,
                false,
                'admin@example.com'
            );
        }
        $input->setOption('account-mail', $account_mail);

        // --account-pass option
        $account_pass = $input->getOption('account-pass');
        if (!$account_pass) {
            $account_pass = $dialog->askHiddenResponse(
                $output,
                $dialog->getQuestion($this->trans('commands.site.install.questions.account-pass'), ''),
                ''
            );
        }
        $input->setOption('account-pass', $account_pass);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Drupal site options
        $profile = $input->getArgument('profile');
        $langcode = $input->getOption('langcode');
        $site_name = $input->getOption('site-name');
        $site_mail = $input->getOption('site-mail');
        $account_name = $input->getOption('account-name');
        $account_mail = $input->getOption('account-mail');
        $account_pass = $input->getOption('account-pass');

        // Database options
        $db_type = $input->getOption('db-type');
        $db_host = $input->getOption('db-host');
        $db_name = $input->getOption('db-name');
        $db_user = $input->getOption('db-user');
        $db_pass = $input->getOption('db-pass');
        $db_prefix = $input->getOption('db-prefix');
        $db_port = $input->getOption('db-port');

        $databases = $this->getDatabaseTypes();

        $database = array(
        'database' => $db_name,
        'username' => $db_user,
        'password' => $db_pass,
        'prefix' => $db_prefix,
        'port' => $db_port,
        'host' => $db_host,
        'namespace' => $databases[$db_type]['namespace'],
        //'namespace' => 'Drupal\Core\Database\Driver\mysql',
        'driver' => 'mysql',
        );

        $this->runInstaller($output, $profile, $langcode, $site_name, $site_mail, $account_name, $account_mail, $account_pass, $database);
    }

    protected function getProfiles()
    {
        $drupal = $this->getDrupalHelper();

        $profiles = $drupal->getProfiles();

        $names = [];
        foreach ($profiles as $profile_key => $profile) {
            $names[$profile_key] = $profile['name'];
        }

        return $names;
    }

    protected function getLanguages()
    {
        $drupal = $this->getDrupalHelper();
        $languages = $drupal->getStandardtLanguages();

        return $languages;
    }

    protected function getDatabaseTypes()
    {
        $drupal = $this->getDrupalHelper();

        $databases = $drupal->getDatabaseTypes();

        return $databases;
    }

    protected function getDefaultLanguage()
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        return $config->get('application.language');
    }

    protected function runInstaller($output, $profile, $langcode, $site_name, $site_mail, $account_name, $account_mail, $account_pass, $database)
    {
        $drupal = $this->getDrupalHelper();
        $drupal->loadLegacyFile('/core/includes/install.core.inc');

        $settings = [
        'parameters' => [
            'profile' => $profile,
            'langcode' => $langcode,
        ],
        'forms' => [
            'install_settings_form' => [
                'driver' => $database['driver'],
                $database['driver'] => $database,
                'op' => 'Save and continue',
            ],
            'install_configure_form' => [
                'site_name' => $site_name,
                'site_mail' => $site_mail,
                'account' => array(
                    'name' => $account_name,
                    'mail' => $account_mail,
                    'pass' => array(
                        'pass1' => $account_pass,
                        'pass2' => $account_pass
                    ),
                ),
                'update_status_module' => array(
                    1 => true,
                    2 => true,
                ),
                'clean_url' =>  true,
                'op' => 'Save and continue',
            ],
        ]
        ];

        $output->writeln('[-] <info>'.$this->trans('commands.site.install.messages.installing').'</info>');

        try {
            install_drupal($drupal->getAutoLoadClass(), $settings);
        } catch (\Exception $e) {
            $output->writeln('[-] <error>' . $e->getMessage() . '</error>');
            return;
        }

        $output->writeln('[-] <info>'.$this->trans('commands.site.install.messages.installed').'</info>');
    }
}
