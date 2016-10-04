<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Multisite\NewCommand.
 */

namespace Drupal\Console\Command\Multisite;

use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class MultisiteNewCommand
 * @package Drupal\Console\Command\Multisite
 */
class NewCommand extends Command
{
    use CommandTrait;

    protected $appRoot;

    /**
     * DebugCommand constructor.
     * @param $appRoot
     */
    public function __construct($appRoot) {
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * @var \Symfony\Component\Filesystem\Filesystem;
     */
    protected $fs;

    /**
     * @var string
     */
    protected $subdir = '';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('multisite:new')
            ->setDescription($this->trans('commands.multisite.new.description'))
            ->setHelp($this->trans('commands.multisite.new.help'))
            ->addArgument(
                'sites-subdir',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.multisite.new.arguments.sites-subdir')
            )
            ->addOption(
                'site-uri',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.multisite.new.options.site-uri')
            )
            ->addOption(
                'copy-install',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.multisite.new.options.copy-install')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);
        $this->subdir = $input->getArgument('sites-subdir');

        if (empty($this->subdir)) {
            $output->error($this->trans('commands.multisite.new.errors.subdir-empty'));
            return 1;
        }

        $this->fs = new Filesystem();

        if ($this->fs->exists($this->appRoot . '/sites/' . $this->subdir)) {
            $output->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.subdir-exists'),
                    $this->subdir
                )
            );

            return 1;
        }

        if (!$this->fs->exists($this->appRoot . '/sites/default')) {
            $output->error($this->trans('commands.multisite.new.errors.default-missing'));
            return 1;
        }

        try {
            $this->fs->mkdir($this->appRoot . '/sites/' . $this->subdir, 0755);
        } catch (IOExceptionInterface $e) {
            $output->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.mkdir-fail'),
                    $this->subdir
                )
            );
            return 1;
        }

        if ($uri = $input->getOption('site-uri')) {
            try {
                $this->addToSitesFile($output, $uri);
            } catch (\Exception $e) {
                $output->error($e->getMessage());
                return 1;
            }
        }

        if ($input->getOption('copy-install')) {
            $this->copyExistingInstall($output);
        }

        $this->createFreshSite($output);

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
        } elseif ($this->fs->exists($this->root . '/sites/example.sites.php')) {
            $sites_file_contents = file_get_contents($this->appRoot . '/sites/example.sites.php');
            $sites_file_contents .= "\n\$sites = [];";
        } else {
            throw new FileNotFoundException($this->trans('commands.multisite.new.errors.sites-missing'));
        }

        $sites_file_contents .= "\n\$sites['$uri'] = '$this->subdir';";

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
     * @param DrupalStyle $output
     */
    protected function copyExistingInstall(DrupalStyle $output)
    {
        if (!$this->fs->exists($this->appRoot . '/sites/default/settings.php')) {
            $output->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.file-missing'),
                    'sites/default/settings.php'
                )
            );
            return;
        }

        if ($this->fs->exists($this->appRoot . '/sites/default/files')) {
            try {
                $this->fs->mirror(
                    $this->appRoot . '/sites/default/files',
                    $this->appRoot . '/sites/' . $this->subdir . '/files'
                );
            } catch (IOExceptionInterface $e) {
                $output->error(
                    sprintf(
                        $this->trans('commands.multisite.new.errors.copy-fail'),
                        'sites/default/files',
                        'sites/' . $this->subdir . '/files'
                    )
                );
                return;
            }
        } else {
            $output->warning($this->trans('commands.multisite.new.warnings.missing-files'));
        }

        $settings = file_get_contents($this->appRoot . '/sites/default/settings.php');
        $settings = str_replace('sites/default', 'sites/' . $this->subdir, $settings);

        try {
            $this->fs->dumpFile(
                $this->appRoot . '/sites/' . $this->subdir . '/settings.php',
                $settings
            );
        } catch (IOExceptionInterface $e) {
            $output->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.write-fail'),
                    'sites/' . $this->subdir . '/settings.php'
                )
            );
            return;
        }

        $this->chmodSettings($output);

        $output->success(
            sprintf(
                $this->trans('commands.multisite.new.messages.copy-install'),
                $this->subdir
            )
        );
    }

    /**
     * Creates site folder with clean settings.php file.
     *
     * @param DrupalStyle $output
     */
    protected function createFreshSite(DrupalStyle $output)
    {
        if ($this->fs->exists($this->appRoot . '/sites/default/default.settings.php')) {
            try {
                $this->fs->copy(
                    $this->appRoot . '/sites/default/default.settings.php',
                    $this->appRoot . '/sites/' . $this->subdir . '/settings.php'
                );
            } catch (IOExceptionInterface $e) {
                $output->error(
                    sprintf(
                        $this->trans('commands.multisite.new.errors.copy-fail'),
                        $this->appRoot . '/sites/default/default.settings.php',
                        $this->appRoot . '/sites/' . $this->subdir . '/settings.php'
                    )
                );
                return;
            }
        } else {
            $output->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.file-missing'),
                    'sites/default/default.settings.php'
                )
            );
            return;
        }

        $this->chmodSettings($output);

        $output->success(
            sprintf(
                $this->trans('commands.multisite.new.messages.fresh-site'),
                $this->subdir
            )
        );
    }

    /**
     * Changes permissions of settings.php to 640.
     *
     * The copy will have 444 permissions by default, which makes it readable by
     * anyone. Also, Drupal likes being able to write to it during, for example,
     * a fresh install.
     *
     * @param DrupalStyle $output
     */
    protected function chmodSettings(DrupalStyle $output)
    {
        try {
            $this->fs->chmod($this->appRoot . '/sites/' . $this->subdir . '/settings.php', 0640);
        } catch (IOExceptionInterface $e) {
            $output->error(
                sprintf(
                    $this->trans('commands.multisite.new.errors.chmod-fail'),
                    $this->appRoot . '/sites/' . $this->subdir . '/settings.php'
                )
            );
        }
    }
}
