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
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Shared\ProjectDownloadTrait;

class NewCommand extends Command
{
    use ProjectDownloadTrait;

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

        if (!$version && $latest) {
            $version = current($this->getDrupalApi()->getProjectReleases('drupal', 1, true));
        }

        $projectPath = $this->downloadProject($io, 'drupal', $version, 'core');
        $downloadPath = sprintf('%sdrupal-%s', $projectPath, $version);
        $copyPath = sprintf('%s%s', $projectPath, $directory);

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

            return;
        }

        $io->success(
            sprintf(
                $this->trans('commands.site.new.messages.downloaded'),
                $version,
                $copyPath
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getArgument('directory');
        $version = $input->getArgument('version');
        $latest = $input->getOption('latest');
        $unstable = $input->getOption('unstable');

        if (!$version && $latest) {
            $version = current($this->getDrupalApi()->getProjectReleases('drupal', 1, true));
        }

        if (!$directory) {
            $directory = $io->ask(
                $this->trans('commands.site.new.questions.directory')
            );
            $input->setArgument('directory', $directory);
        }


        if (!$version) {
            $version = $this->releasesQuestion($io, 'drupal', false, $unstable?false:true);
            $input->setArgument('version', $version);
        }
    }

    protected function isAbsolutePath($path)
    {
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0;
    }
}
