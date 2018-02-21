<?php

namespace Drupal\Console\Command\Dotenv;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Component\Utility\Crypt;
use Drupal\Console\Generator\DotenvInitGenerator;
use Webmozart\PathUtil\Path;
use Drupal\Console\Core\Utils\DrupalFinder;

/**
 * Class InitCommand
 *
 * @package Drupal\Console\Command\Dotenv
 */
class InitCommand extends Command
{
    /**
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * InitCommand constructor.
     *
     * @param DrupalFinder $drupalFinder
     */

    /**
     * @var DotenvInitGenerator
     */
    protected $generator;

    private $envParameters = [
        'environment' => 'local',
        'database_name' => 'drupal',
        'database_user' => 'drupal',
        'database_password' => 'drupal',
        'database_host' => '127.0.0.1',
        'database_port' => '3306',
    ];

    /**
     * InitCommand constructor.
     *
     * @param DrupalFinder        $drupalFinder
     * @param DotenvInitGenerator $generator
     */
    public function __construct(
        DrupalFinder $drupalFinder,
        DotenvInitGenerator $generator
    ) {
        $this->drupalFinder = $drupalFinder;
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('dotenv:init')
            ->setDescription('Dotenv initializer.');
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
        $this->copyFiles();

        $this->generator->generate([
            'io' => $this->getIo(),
            'env_parameters' => $this->envParameters,
            'console_root' => $this->drupalFinder->getComposerRoot(),
        ]);
    }

    private function copyFiles()
    {
        $fs = new Filesystem();
        $defaultSettingsFile = $this->drupalFinder->getDrupalRoot() . '/sites/default/default.settings.php';
        $settingsFile = $this->drupalFinder->getDrupalRoot() . '/sites/default/settings.php';

        if (!$fs->exists($defaultSettingsFile)) {
            $defaultSettingsFile = Path::makeRelative(
                $defaultSettingsFile,
                $this->drupalFinder->getComposerRoot()
            );
            $this->getIo()->error('File: ' . $defaultSettingsFile . 'not found.');

            return 1;
        }

        if ($fs->exists($settingsFile)) {
            $settingsFileOriginal = $settingsFile.'.original';
            if (!$fs->exists($settingsFileOriginal)) {
                $fs->rename(
                    $settingsFile,
                    $settingsFileOriginal,
                    true
                );


                $settingsOriginalFile = Path::makeRelative(
                    $settingsFile,
                    $this->drupalFinder->getComposerRoot()
                );

                $this->getIo()->success('File '.$settingsOriginalFile.'.original created.');
            }
        }

        $fs->copy(
            $defaultSettingsFile,
            $settingsFile
        );

        include_once $this->drupalFinder->getDrupalRoot() . '/core/includes/bootstrap.inc';
        include_once $this->drupalFinder->getDrupalRoot() . '/core/includes/install.inc';

        $settings['config_directories'] = [
            CONFIG_SYNC_DIRECTORY => (object) [
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

        drupal_rewrite_settings($settings, $settingsFile);

        $settingsFileContent = file_get_contents($settingsFile);
        file_put_contents(
            $settingsFile,
            $settingsFileContent .
            file_get_contents(
                $this->drupalFinder->getConsolePath() . 'templates/files/settings.dist'
            )
        );

        $fs->chmod($settingsFile, 0666);

        $settingsFile = Path::makeRelative(
            $settingsFile,
            $this->drupalFinder->getComposerRoot()
        );

        $this->getIo()->success('File '.$settingsFile.' created.');

        $gitIgnoreFile = $this->drupalFinder->getComposerRoot() . '/.gitignore';
        $gitIgnoreExampleFile = $this->drupalFinder->getComposerRoot() . '/example.gitignore';
        if (!$fs->exists($gitIgnoreFile)) {
            if (!$fs->exists($gitIgnoreExampleFile)) {
                $fs->copy(
                    $gitIgnoreExampleFile,
                    $gitIgnoreFile
                );
            }
        }

        if ($fs->exists($gitIgnoreFile)) {
            $gitIgnoreContent = file_get_contents($gitIgnoreFile);
            if (strpos($gitIgnoreContent, '.env') === false) {
                file_put_contents(
                    $gitIgnoreFile,
                    $gitIgnoreContent .
                    file_get_contents(
                        $this->drupalFinder->getConsolePath() . 'templates/files/.gitignore.dist'
                    )
                );

                $this->getIo()->success("File .gitignore updated.");
            }
        }
    }
}
