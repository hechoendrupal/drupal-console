<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Multisite\NewCommand.
 */

namespace Drupal\Console\Command\Multisite;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class NewCommand
 *
 * @package Drupal\Console\Command\Multisite
 */
class NewCommand extends Command
{
    protected $appRoot;

    /**
     * DebugCommand constructor.
     *
     * @param $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * @var Filesystem;
     */
    protected $fs;

    /**
     * @var string
     */
    protected $directory = '';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('multisite:new')
            ->setDescription($this->trans('commands.multisite.new.description'))
            ->setHelp($this->trans('commands.multisite.new.help'))
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                $this->trans('commands.multisite.new.arguments.directory')
            )
            ->addArgument(
                'uri',
                InputArgument::REQUIRED,
                $this->trans('commands.multisite.new.arguments.uri')
            )
            ->addOption(
                'copy-default',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.multisite.new.options.copy-default')
            )
            ->setAliases(['mun']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->fs = new Filesystem();
        $this->directory = $input->getArgument('directory');

        if (!$this->directory) {
            $io->error($this->trans('commands.multisite.new.errors.subdir-empty'));

            return 1;
        }

        if ($this->fs->exists($this->appRoot . '/sites/' . $this->directory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.subdir-exists'),
                    $this->directory
                )
            );

            return 1;
        }

        if (!$this->fs->exists($this->appRoot . '/sites/default')) {
            $io->error($this->trans('commands.multisite.new.errors.default-missing'));

            return 1;
        }

        try {
            $this->fs->mkdir($this->appRoot . '/sites/' . $this->directory, 0755);
        } catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.mkdir-fail'),
                    $this->directory
                )
            );

            return 1;
        }

        $uri = $input->getArgument('uri');
        try {
            $this->addToSitesFile($io, $uri);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->createFreshSite($io);

        return 0;
    }

    /**
     * Adds line to sites.php that is needed for the new site to be recognized.
     *
     * @param DrupalStyle $output
     * @param string      $uri
     *
     * @throws FileNotFoundException
     */
    protected function addToSitesFile(DrupalStyle $output, $uri)
    {
        if ($this->fs->exists($this->appRoot . '/sites/sites.php')) {
            $sites_is_dir = is_dir($this->appRoot . '/sites/sites.php');
            $sites_readable = is_readable($this->appRoot . '/sites/sites.php');
            if ($sites_is_dir || !$sites_readable) {
                throw new FileNotFoundException($this->trans('commands.multisite.new.errors.sites-invalid'));
            }
            $sites_file_contents = file_get_contents($this->appRoot . '/sites/sites.php');
        } elseif ($this->fs->exists($this->appRoot . '/sites/example.sites.php')) {
            $sites_file_contents = file_get_contents($this->appRoot . '/sites/example.sites.php');
            $sites_file_contents .= "\n\$sites = [];";
        } else {
            throw new FileNotFoundException($this->trans('commands.multisite.new.errors.sites-missing'));
        }

        $sites_file_contents .= "\n\$sites['$this->directory'] = '$this->directory';";

        try {
            $this->fs->dumpFile($this->appRoot . '/sites/sites.php', $sites_file_contents);
            $this->fs->chmod($this->appRoot . '/sites/sites.php', 0640);
        } catch (IOExceptionInterface $e) {
            $output->error('commands.multisite.new.errors.sites-other');
        }
    }

    /**
     * Copies detected default install alters settings.php to fit the new directory.
     *
     * @param DrupalStyle $io
     */
    protected function copyExistingInstall(DrupalStyle $io)
    {
        if (!$this->fs->exists($this->appRoot . '/sites/default/settings.php')) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.file-missing'),
                    'sites/default/settings.php'
                )
            );
            return 1;
        }

        if ($this->fs->exists($this->appRoot . '/sites/default/files')) {
            try {
                $this->fs->mirror(
                    $this->appRoot . '/sites/default/files',
                    $this->appRoot . '/sites/' . $this->directory . '/files'
                );
            } catch (IOExceptionInterface $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.multisite.new.errors.copy-fail'),
                        'sites/default/files',
                        'sites/' . $this->directory . '/files'
                    )
                );
                return 1;
            }
        } else {
            $io->warning($this->trans('commands.multisite.new.warnings.missing-files'));
        }

        $settings = file_get_contents($this->appRoot . '/sites/default/settings.php');
        $settings = str_replace('sites/default', 'sites/' . $this->directory, $settings);

        try {
            $this->fs->dumpFile(
                $this->appRoot . '/sites/' . $this->directory . '/settings.php',
                $settings
            );
        } catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.write-fail'),
                    'sites/' . $this->directory . '/settings.php'
                )
            );
            return 1;
        }

        $this->chmodSettings($io);

        $io->success(
            sprintf(
                $this->trans('commands.multisite.new.messages.copy-default'),
                $this->directory
            )
        );
    }

    /**
     * Creates site folder with clean settings.php file.
     *
     * @param DrupalStyle $io
     */
    protected function createFreshSite(DrupalStyle $io)
    {
        if ($this->fs->exists($this->appRoot . '/sites/default/default.settings.php')) {
            try {
                $this->fs->copy(
                    $this->appRoot . '/sites/default/default.settings.php',
                    $this->appRoot . '/sites/' . $this->directory . '/settings.php'
                );
            } catch (IOExceptionInterface $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.multisite.new.errors.copy-fail'),
                        $this->appRoot . '/sites/default/default.settings.php',
                        $this->appRoot . '/sites/' . $this->directory . '/settings.php'
                    )
                );
                return 1;
            }
        } else {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.file-missing'),
                    'sites/default/default.settings.php'
                )
            );
            return 1;
        }

        $this->chmodSettings($io);

        $io->success(
            sprintf(
                $this->trans('commands.multisite.new.messages.fresh-site'),
                $this->directory
            )
        );

        return 0;
    }

    /**
     * Changes permissions of settings.php to 640.
     *
     * The copy will have 444 permissions by default, which makes it readable by
     * anyone. Also, Drupal likes being able to write to it during, for example,
     * a fresh install.
     *
     * @param DrupalStyle $io
     */
    protected function chmodSettings(DrupalStyle $io)
    {
        try {
            $this->fs->chmod($this->appRoot . '/sites/' . $this->directory . '/settings.php', 0640);
        } catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.chmod-fail'),
                    $this->appRoot . '/sites/' . $this->directory . '/settings.php'
                )
            );

            return 1;
        }
    }
}
