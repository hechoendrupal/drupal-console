<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Site\NewCommand.
 *
 *
  @TODO: colorized command output
  @TODO: add a config option for sn & md *always* using --composer option in each site
  (let's say in «my_site.prod» i want install & download all the modules using composer)
  @TODO: wrap in a Trait the «command execution» logic for using it here  & in md
  @TODO: patch to drupal modules support
 *
 *
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
use Drupal\Console\Command\ProjectDownloadTrait;
use Drupal\Console\Command\PHPProcessTrait;

class NewCommand extends Command
{
    use ProjectDownloadTrait;
    use PHPProcessTrait;


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

        if (!$version && $latest) {
            $version = current($this->getDrupalApi()->getProjectReleases('drupal', 1, true));
        }

        if ($composer)
        {
          $cmd = "composer create-project drupal/drupal $directory $version --no-interaction";
          if ( $this->ExecProcess($cmd) )
          {
            $io->success(
              sprintf(
                  $this->trans('commands.site.new.messages.composer'),
                  $version
              )
            );

            return 1;
          }
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
        $composer = $input->getOption('composer');

        if (!$version && $latest) {

            //@TODO: if ($composer) get packagist drupal versions !!

            // for now, this works as long as the selected version
            // coincides the packagist version

            $version = current($this->getDrupalApi()->getProjectReleases('drupal', 1, true));
        }

        if (!$directory) {
            $directory = $io->ask(
                $this->trans('commands.site.new.questions.directory')
            );
            $input->setArgument('directory', $directory);
        }


        if (!$version) {
            $version = $this->releasesQuestion($io, 'drupal', false, true);
            $input->setArgument('version', $version);
        }
    }

    protected function isAbsolutePath($path)
    {
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0;
    }
}
