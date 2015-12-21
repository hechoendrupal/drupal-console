<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Site\InstallCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\Core\Database\Database;
use Drupal\Core\Installer\Exception\AlreadyInstalledException;
use Drupal\Console\Command\Database\DatabaseTrait;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class InstallCommand extends Command
{
    use DatabaseTrait;

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
                'db-file',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.arguments.db-file')
            )
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_OPTIONAL,
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
                InputOption::VALUE_OPTIONAL,
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
        $io = new DrupalStyle($input, $output);

        // profile option
        $profile = $input->getArgument('profile');
        if (!$profile) {
            $profiles = $this->getProfiles();
            $profile = $io->choice(
                $this->trans('commands.site.install.questions.profile'),
                array_values($profiles)
            );
            $input->setArgument('profile', array_search($profile, $profiles));
        }

        // --langcode option
        $langcode = $input->getOption('langcode');
        if (!$langcode) {
            $languages = $this->getLanguages();
            $defaultLanguage = $this->getDefaultLanguage();

            $langcode = $io->choiceNoList(
                $this->trans('commands.site.install.questions.langcode'),
                $languages,
                $languages[$defaultLanguage]
            );

            $input->setOption('langcode', $langcode);
        }

        // Use default database setting if is available
        $database = Database::getConnectionInfo();
        if (empty($database['default'])) {

            // --db-type option
            $dbType = $input->getOption('db-type');
            if (!$dbType) {
                $dbType = $this->dbTypeQuestion($io);
                $input->setOption('db-type', $dbType);
            }

            // --db-file option
            $dbFile = $input->getOption('db-file');
            if ($dbType == 'sqlite' && !$dbFile) {
                $dbFile = $this->dbFileQuestion($io);
                $input->setOption('db-file', $dbFile);
            } else {
                // --db-host option
                $dbHost = $input->getOption('db-host');
                if (!$dbHost) {
                    $dbHost = $this->dbHostQuestion($io);
                    $input->setOption('db-host', $dbHost);
                }

                // --db-name option
                $dbName = $input->getOption('db-name');
                if (!$dbName) {
                    $dbName = $this->dbNameQuestion($io);
                    $input->setOption('db-name', $dbName);
                }

                // --db-user option
                $dbUser = $input->getOption('db-user');
                if (!$dbUser) {
                    $dbUser = $this->dbUserQuestion($io);
                    $input->setOption('db-user', $dbUser);
                }

                // --db-pass option
                $dbPass = $input->getOption('db-pass');
                if (!$dbPass) {
                    $dbPass = $this->dbPassQuestion($io);
                    $input->setOption('db-pass', $dbPass);
                }

                // --db-port prefix
                $dbPort = $input->getOption('db-port');
                if (!$dbPort) {
                    $dbPort = $this->dbPortQuestion($io);
                    $input->setOption('db-port', $dbPort);
                }
            }

            // --db-prefix
            $dbPrefix = $input->getOption('db-prefix');
            if (!$dbPrefix) {
                $dbPrefix = $this->dbPrefixQuestion($io);
                $input->setOption('db-prefix', $dbPrefix);
            }
        } else {
            $input->setOption('db-type', $database['default']['driver']);
            $input->setOption('db-host', $database['default']['host']);
            $input->setOption('db-name', $database['default']['database']);
            $input->setOption('db-user', $database['default']['username']);
            $input->setOption('db-pass', $database['default']['password']);
            $input->setOption('db-port', $database['default']['port']);
            $input->setOption('db-prefix', $database['default']['prefix']['default']);
            $io->info(
                sprintf(
                    $this->trans('commands.site.install.messages.using-current-database'),
                    $database['default']['driver'],
                    $database['default']['database'],
                    $database['default']['username']
                )
            );
        }

        // --site-name option
        $site_name = $input->getOption('site-name');
        if (!$site_name) {
            $site_name = $io->ask(
                $this->trans('commands.site.install.questions.site-name'),
                'Drupal 8 Site Install'
            );
            $input->setOption('site-name', $site_name);
        }

        // --site-mail option
        $site_mail = $input->getOption('site-mail');
        if (!$site_mail) {
            $site_mail = $io->ask(
                $this->trans('commands.site.install.questions.site-mail'),
                'admin@example.com'
            );
            $input->setOption('site-mail', $site_mail);
        }

        // --account-name option
        $account_name = $input->getOption('account-name');
        if (!$account_name) {
            $account_name = $io->ask(
                $this->trans('commands.site.install.questions.account-name'),
                'admin'
            );
            $input->setOption('account-name', $account_name);
        }

        // --account-mail option
        $account_mail = $input->getOption('account-mail');
        if (!$account_mail) {
            $account_mail = $io->ask(
                $this->trans('commands.site.install.questions.account-mail'),
                'admin@example.com'
            );
            $input->setOption('account-mail', $account_mail);
        }

        // --account-pass option
        $account_pass = $input->getOption('account-pass');
        if (!$account_pass) {
            $account_pass = $io->askHidden(
                $this->trans('commands.site.install.questions.account-pass')
            );
            $input->setOption('account-pass', $account_pass);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // Database options
        $dbType = $input->getOption('db-type');
        $dbFile = $input->getOption('db-file');
        $dbHost = $input->getOption('db-host');
        $dbName = $input->getOption('db-name');
        $dbUser = $input->getOption('db-user');
        $dbPass = $input->getOption('db-pass');
        $dbPrefix = $input->getOption('db-prefix');
        $dbPort = $input->getOption('db-port');

        $databases = $this->getDatabaseTypes();

        if ($dbType == 'sqlite') {
            $database = array(
              'database' => $dbFile,
              'prefix' => $dbPrefix,
              'namespace' => $databases[$dbType]['namespace'],
              'driver' => $dbType,
            );
        } else {
            $database = array(
              'database' => $dbName,
              'username' => $dbUser,
              'password' => $dbPass,
              'prefix' => $dbPrefix,
              'port' => $dbPort,
              'host' => $dbHost,
              'namespace' => $databases[$dbType]['namespace'],
              'driver' => $dbType,
            );
        }

        try {
            $this->runInstaller($output, $input, $database);
        } catch (Exception $e) {
            $output->error($e->getMessage());
            return;
        }
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
        $languages = $drupal->getStandardLanguages();

        return $languages;
    }

    protected function getDefaultLanguage()
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        return $config->get('application.language');
    }

    protected function runInstaller(
        DrupalStyle $output,
        InputInterface $input,
        $database
    ) {
        $drupal = $this->getDrupalHelper();
        $drupal->loadLegacyFile('/core/includes/install.core.inc');

        $settings = [
            'parameters' => [
                'profile' => $input->getArgument('profile'),
                'langcode' => $input->getOption('langcode'),
            ],
            'forms' => [
                'install_settings_form' => [
                    'driver' => $database['driver'],
                    $database['driver'] => $database,
                    'op' => 'Save and continue',
                ],
                'install_configure_form' => [
                    'site_name' => $input->getOption('site-name'),
                    'site_mail' => $input->getOption('site-mail'),
                    'account' => array(
                        'name' => $input->getOption('account-name'),
                        'mail' => $input->getOption('account-mail'),
                        'pass' => array(
                            'pass1' => $input->getOption('account-pass'),
                            'pass2' => $input->getOption('account-pass')
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

        $output->writeln($this->trans('commands.site.install.messages.installing'));

        try {
            install_drupal($drupal->getAutoLoadClass(), $settings);
        } catch (AlreadyInstalledException $e) {
            $output->error($this->trans('commands.site.install.messages.already-installed'));
            return;
        } catch (\Exception $e) {
            $output->error($e->getMessage());
            return;
        }

        $output->success($this->trans('commands.site.install.messages.installed'));
    }
}
