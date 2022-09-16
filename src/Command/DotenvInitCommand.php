<?php

namespace Drupal\Console\Command;

use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\GenerateCommand;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Component\Utility\Crypt;
use Drupal\Console\Generator\DotenvInitGenerator;
use Webmozart\PathUtil\Path;

/**
 * Class InitCommand
 *
 * @package Drupal\Console\Command\Dotenv
 */
class DotenvInitCommand extends GenerateCommand
{
    /**
     * @var DotenvInitGenerator
     */
    protected $generator;

    private $envParameters = [
        'environment' => 'develop',
        'database_name' => 'drupal',
        'database_user' => 'drupal',
        'database_password' => 'drupal',
        'database_host' => 'mariadb',
        'database_port' => '3306',
        'host_name' => 'drupal.develop',
        'host_port' => '80',
        'drupal_root' => '/var/www/html',
        'server_root' => '/var/www/html/web'
    ];

    /**
     * InitCommand constructor.
     *
     * @param DotenvInitGenerator $generator
     */
    public function __construct(
        DotenvInitGenerator $generator
    ) {
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('dotenv:init')
            ->setDescription($this->trans('commands.dotenv.init.description'))
            ->addOption(
                'load-from-env',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.dotenv.init.options.load-from-env')
            )
            ->addOption(
                'load-settings',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.dotenv.init.options.load-settings')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->envParameters as $key => $value) {
            $this->envParameters[$key] = $this->getIo()->ask(
                'Enter value for ' . strtoupper($key),
                $value
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $loadFromEnv = $input->getOption('load-from-env');
        $loadSettings = $input->getOption('load-settings');
        if ($loadFromEnv) {
            $this->envParameters['load_from_env'] = $loadFromEnv;
        }
        if ($loadSettings) {
            $this->envParameters['load_settings'] = $loadSettings;
        }
        $this->copySettingsFile($fs);
        $this->copyEnvFile($fs);

        $this->generator->setIo($this->getIo());
        $this->generator->generate($this->envParameters);
    }

    protected function copySettingsFile(Filesystem $fs)
    {
        $sourceFile = $this->drupalFinder
                ->getDrupalRoot() . '/sites/default/default.settings.php';
        $destinationFile = $this->drupalFinder
                ->getDrupalRoot() . '/sites/default/settings.php';

        $directory = dirname($sourceFile);
        $permissions = fileperms($directory);
        $fs->chmod($directory, 0755);

        $this->validateFileExists($fs, $sourceFile);
        $this->backUpFile($fs, $destinationFile);

        $fs->copy(
            $sourceFile,
            $destinationFile
        );

        $this->validateFileExists($fs, $destinationFile);

        include_once $this->drupalFinder->getDrupalRoot() . '/core/includes/bootstrap.inc';
        include_once $this->drupalFinder->getDrupalRoot() . '/core/includes/install.inc';

        $settings['config_directories'] = [
            Settings::get('config_sync_directory') => (object) [
                'value' => Path::makeRelative(
                    $this->drupalFinder->getComposerRoot() . '/config/sync',
                    $this->drupalFinder->getDrupalRoot()
                ),
                'required' => true,
            ],
        ];

        $settings['settings']['hash_salt'] = (object) [
            'value'    => Crypt::randomBytesBase64(55),
            'required' => true,
        ];

        drupal_rewrite_settings($settings, $destinationFile);

        $this->showFileCreatedMessage($destinationFile);

        $fs->chmod($directory, $permissions);
    }

    private function copyEnvFile(Filesystem $fs)
    {
        $sourceFiles = [
            $this->drupalFinder->getComposerRoot() . '/example.gitignore',
            $this->drupalFinder->getComposerRoot() . '/.gitignore'
        ];

        $sourceFile = $this->validateFileExists($fs, $sourceFiles);

        $destinationFile = $this->drupalFinder
                ->getComposerRoot() . '/.gitignore';

        if ($sourceFile !== $destinationFile) {
            $this->backUpFile($fs, $destinationFile);
        }

        $fs->copy(
            $sourceFile,
            $destinationFile
        );

        $this->validateFileExists($fs, $destinationFile);

        $gitIgnoreContent = file_get_contents($destinationFile);
        $gitIgnoreDistFile = $this->drupalFinder->getComposerRoot() .
            $this->drupalFinder->getConsolePath() .
            'templates/files/.gitignore.dist';
        $gitIgnoreDistContent = file_get_contents($gitIgnoreDistFile);

        if (strpos($gitIgnoreContent, '.env') === false) {
            file_put_contents(
                $destinationFile,
                $gitIgnoreContent .
                $gitIgnoreDistContent
            );
        }

        $this->showFileCreatedMessage($destinationFile);
    }
}
