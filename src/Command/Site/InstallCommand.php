<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Site\InstallCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Database\Database;
use Drupal\Core\Installer\Exception\AlreadyInstalledException;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\DatabaseTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Console\Utils\Site;
use DrupalFinder\DrupalFinder;

class InstallCommand extends Command
{
    use ContainerAwareCommandTrait;
    use DatabaseTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var  ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * InstallCommand constructor.
     *
     * @param Manager              $extensionManager
     * @param Site                 $site
     * @param ConfigurationManager $configurationManager
     * @param string               $appRoot
     */
    public function __construct(
        Manager $extensionManager,
        Site $site,
        ConfigurationManager $configurationManager,
        $appRoot
    ) {
        $this->extensionManager = $extensionManager;
        $this->site = $site;
        $this->configurationManager = $configurationManager;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('site:install')
            ->setDescription($this->trans('commands.site.install.description'))
            ->addArgument(
                'profile',
                InputArgument::OPTIONAL,
                $this->trans('commands.site.install.arguments.profile')
            )
            ->addOption(
                'langcode',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.langcode')
            )
            ->addOption(
                'db-type',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.db-type')
            )
            ->addOption(
                'db-file',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.db-file')
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
                $this->trans('commands.site.install.options.site-name')
            )
            ->addOption(
                'site-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.site-mail')
            )
            ->addOption(
                'account-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.account-name')
            )
            ->addOption(
                'account-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.account-mail')
            )
            ->addOption(
                'account-pass',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.account-pass')
            )
            ->addOption(
                'force',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.site.install.options.force')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --profile option
        $profile = $input->getArgument('profile');
        if (!$profile) {
            $profiles = $this->extensionManager
                ->discoverProfiles()
                ->showCore()
                ->showNoCore()
                ->showInstalled()
                ->showUninstalled()
                ->getList(true);

            $profiles = array_filter(
                $profiles,
                function ($profile) {
                    return strpos($profile, 'testing') !== 0;
                }
            );

            $profile = $io->choice(
                $this->trans('commands.site.install.questions.profile'),
                array_values($profiles)
            );

            $input->setArgument('profile', $profile);
        }

        // --langcode option
        $langcode = $input->getOption('langcode');
        if (!$langcode) {
            $languages = $this->site->getStandardLanguages();
            $defaultLanguage = $this->configurationManager
                ->getConfiguration()
                ->get('application.language');

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
                $databases = $this->site->getDatabaseTypes();
                $dbType = $io->choice(
                    $this->trans('commands.migrate.setup.questions.db-type'),
                    array_column($databases, 'name')
                );

                foreach ($databases as $dbIndex => $database) {
                    if ($database['name'] == $dbType) {
                        $dbType = $dbIndex;
                    }
                }

                $input->setOption('db-type', $dbType);
            }

            if ($dbType === 'sqlite') {
                // --db-file option
                $dbFile = $input->getOption('db-file');
                if (!$dbFile) {
                    $dbFile = $io->ask(
                        $this->trans('commands.migrate.execute.questions.db-file'),
                        'sites/default/files/.ht.sqlite'
                    );
                    $input->setOption('db-file', $dbFile);
                }
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
        $siteName = $input->getOption('site-name');
        if (!$siteName) {
            $siteName = $io->ask(
                $this->trans('commands.site.install.questions.site-name'),
                'Drupal 8'
            );
            $input->setOption('site-name', $siteName);
        }

        // --site-mail option
        $siteMail = $input->getOption('site-mail');
        if (!$siteMail) {
            $siteMail = $io->ask(
                $this->trans('commands.site.install.questions.site-mail'),
                'admin@example.com'
            );
            $input->setOption('site-mail', $siteMail);
        }

        // --account-name option
        $accountName = $input->getOption('account-name');
        if (!$accountName) {
            $accountName = $io->ask(
                $this->trans('commands.site.install.questions.account-name'),
                'admin'
            );
            $input->setOption('account-name', $accountName);
        }

        // --account-pass option
        $accountPass = $input->getOption('account-pass');
        if (!$accountPass) {
            $accountPass = $io->askHidden(
                $this->trans('commands.site.install.questions.account-pass')
            );
            $input->setOption('account-pass', $accountPass);
        }

        // --account-mail option
        $accountMail = $input->getOption('account-mail');
        if (!$accountMail) {
            $accountMail = $io->ask(
                $this->trans('commands.site.install.questions.account-mail'),
                $siteMail
            );
            $input->setOption('account-mail', $accountMail);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $uri =  parse_url($input->getParameterOption(['--uri', '-l'], 'default'), PHP_URL_HOST);

        if ($this->site->multisiteMode($uri)) {
            if (!$this->site->validMultisite($uri)) {
                $io->error(
                    sprintf($this->trans('commands.site.install.messages.invalid-multisite'), $uri, $uri)
                );
                exit(1);
            }

            // Modify $_SERVER environment information to enable
            // the Drupal installer to use the multi-site configuration.
            $_SERVER['HTTP_HOST'] = $uri;
        }

        // Database options
        $dbType = $input->getOption('db-type')?:'mysql';
        $dbFile = $input->getOption('db-file');
        $dbHost = $input->getOption('db-host')?:'127.0.0.1';
        $dbName = $input->getOption('db-name')?:'drupal_'.time();
        $dbUser = $input->getOption('db-user')?:'root';
        $dbPass = $input->getOption('db-pass');
        $dbPrefix = $input->getOption('db-prefix');
        $dbPort = $input->getOption('db-port')?:'3306';
        $force = $input->getOption('force');

        $databases = $this->site->getDatabaseTypes();

        if ($dbType === 'sqlite') {
            $database = [
              'database' => $dbFile,
              'prefix' => $dbPrefix,
              'namespace' => $databases[$dbType]['namespace'],
              'driver' => $dbType,
            ];

            if ($force) {
                $fs = new Filesystem();
                $fs->remove($dbFile);
            }
        } else {
            $database = [
              'database' => $dbName,
              'username' => $dbUser,
              'password' => $dbPass,
              'prefix' => $dbPrefix,
              'port' => $dbPort,
              'host' => $dbHost,
              'namespace' => $databases[$dbType]['namespace'],
              'driver' => $dbType,
            ];

            if ($force && Database::getConnectionInfo()) {
                $schema = Database::getConnection()->schema();
                $tables = $schema->findTables('%');
                foreach ($tables as $table) {
                    $schema->dropTable($table);
                }
            }
        }

        try {
            $drupalFinder = new DrupalFinder();
            $drupalFinder->locateRoot(getcwd());
            $composerRoot = $drupalFinder->getComposerRoot();
            $drupalRoot = $drupalFinder->getDrupalRoot();

            $this->runInstaller($io, $input, $database, $uri);

            $autoload = $this->container->get('class_loader');
            $drupal = new Drupal($autoload, $composerRoot, $drupalRoot);
            $container = $drupal->boot();
            $this->getApplication()->setContainer($container);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return;
        }

        $this->restoreSitesFile($io);
    }

    /**
     * Backs up sites.php to backup.sites.php (if needed).
     *
     * This is needed because of a bug with install_drupal() that causes the
     * install files to be placed directly under /sites instead of the
     * appropriate subdir when run from a script and a sites.php file exists.
     *
     * @param DrupalStyle $output
     */
    protected function backupSitesFile(DrupalStyle $output)
    {
        if (!file_exists($this->appRoot . '/sites/sites.php')) {
            return;
        }

        rename($this->appRoot . '/sites/sites.php', $this->appRoot . '/sites/backup.sites.php');

        $output->info($this->trans('commands.site.install.messages.sites-backup'));
    }

    /**
     * Restores backup.sites.php to sites.php (if needed).
     *
     * @param DrupalStyle $output
     */
    protected function restoreSitesFile(DrupalStyle $output)
    {
        if (!file_exists($this->appRoot . '/sites/backup.sites.php')) {
            return;
        }

        rename($this->appRoot . '/sites/backup.sites.php', $this->appRoot . '/sites/sites.php');

        $output->info($this->trans('commands.site.install.messages.sites-restore'));
    }

    protected function runInstaller(
        DrupalStyle $io,
        InputInterface $input,
        $database,
        $uri
    ) {
        $this->site->loadLegacyFile('/core/includes/install.core.inc');

        $driver = (string)$database['driver'];

        $settings = [
            'parameters' => [
                'profile' => $input->getArgument('profile') ?: 'standard',
                'langcode' => $input->getOption('langcode') ?: 'en',
            ],
            'forms' => [
                'install_settings_form' => [
                    'driver' => $driver,
                    $driver => $database,
                    'op' => 'Save and continue',
                ],
                'install_configure_form' => [
                    'site_name' => $input->getOption('site-name') ?: 'Drupal 8',
                    'site_mail' => $input->getOption('site-mail') ?: 'admin@example.org',
                    'account' => [
                        'name' => $input->getOption('account-name') ?: 'admin',
                        'mail' => $input->getOption('account-mail') ?: 'admin@example.org',
                        'pass' => [
                            'pass1' => $input->getOption('account-pass') ?: 'admin',
                            'pass2' => $input->getOption('account-pass') ?: 'admin'
                        ],
                    ],
                    'update_status_module' => [
                        1 => true,
                        2 => true,
                    ],
                    'clean_url' => true,
                    'op' => 'Save and continue',
                ],
            ]
        ];

        if (!$this->site->multisiteMode($uri)) {
            $this->backupSitesFile($io);
        }

        $io->newLine();
        $io->info($this->trans('commands.site.install.messages.installing'));

        try {
            $autoload = $this->site->getAutoload();
            install_drupal($autoload, $settings);
        } catch (AlreadyInstalledException $e) {
            $io->error($this->trans('commands.site.install.messages.already-installed'));
            return;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return;
        }

        if (!$this->site->multisiteMode($uri)) {
            $this->restoreSitesFile($io);
        }

        $io->success($this->trans('commands.site.install.messages.installed'));
    }
}
