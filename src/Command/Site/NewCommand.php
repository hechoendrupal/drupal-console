<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Site\NewCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;

class NewCommand extends Command
{
    use ProjectDownloadTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('site:new')
            ->setDescription($this->trans('commands.site.new.description'))
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                $this->trans('commands.site.new.arguments.directory')
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                $this->trans('commands.site.new.arguments.version')
            )
            ->addOption(
                'latest',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.site.new.options.latest')
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.site.new.options.composer')
            )
            ->addOption(
                'unstable',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.site.new.options.unstable')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getArgument('directory');
        $version = $input->getArgument('version');
        $latest = $input->getOption('latest');
        $composer = $input->getOption('composer');

        if (!$directory) {
            $io->error(
                $this->trans('commands.site.new.messages.missing-directory')
            );

            return 1;
        }

        if ($composer) {
            if (!$version) {
                $version = '8.x-dev';
            }

            $io->newLine();
            $io->comment(
                sprintf(
                    $this->trans('commands.site.new.messages.executing'),
                    'drupal',
                    $version
                )
            );

            $command = sprintf(
                'composer create-project %s:%s %s --no-interaction',
                'drupal-composer/drupal-project',
                $version,
                $directory
            );

            $io->commentBlock($command);

            $shellProcess = $this->get('shell_process');
            if ($shellProcess->exec($command)) {
                $io->success(
                    sprintf(
                        $this->trans('commands.site.new.messages.composer'),
                        $version,
                        $directory
                    )
                );

                return 0;
            } else {
                return 1;
            }
        }

        if (!$version && $latest) {
            $version = current(
                $this->getApplication()->getDrupalApi()->getProjectReleases('drupal', 1, true)
            );
        }

        if (!$version) {
            $io->error('Missing version');

            return 1;
        }

        $projectPath = $this->downloadProject($io, 'drupal', $version, 'core');
        $downloadPath = sprintf('%sdrupal-%s', $projectPath, $version);

        if ($this->isAbsolutePath($directory)) {
            $copyPath = $directory;
        } else {
            $copyPath = sprintf('%s%s', $projectPath, $directory);
        }

        try {
            $fileSystem = new Filesystem();
            $fileSystem->rename($downloadPath, $copyPath);
        } catch (IOExceptionInterface $e) {
            $io->commentBlock(
                sprintf(
                    $this->trans('commands.site.new.messages.downloaded'),
                    $version,
                    $downloadPath
                )
            );

            $io->error(
                sprintf(
                    $this->trans('commands.site.new.messages.error-copying'),
                    $e->getPath()
                )
            );

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.site.new.messages.downloaded'),
                $version,
                $copyPath
            )
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getArgument('directory');
        $version   = $input->getArgument('version');
        $latest    = $input->getOption('latest');
        $unstable  = $input->getOption('unstable');
        $composer  = $input->getOption('composer');

        if (!$directory) {
            $directory = $io->ask(
                $this->trans('commands.site.new.questions.directory')
            );
            $input->setArgument('directory', $directory);
        }

        if ($composer) {
            $input->setArgument('version', '8.x-dev');

            return 0;
        }

        if (!$version && $latest) {
            $version = current(
                $this->getApplication()->getDrupalApi()->getProjectReleases('drupal', 1, true)
            );
        }

        if (!$version) {
            $version = $this->releasesQuestion($io, 'drupal', false, !$unstable);
        }

        $input->setArgument('version', $version);

        return 0;
    }

    protected function isAbsolutePath($path)
    {
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0;
    }
}
