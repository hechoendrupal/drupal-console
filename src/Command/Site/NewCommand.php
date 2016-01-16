<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Site\NewCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\ProjectDownloadTrait;

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

        $projectPath = $this->downloadProject($io, 'drupal', $version, 'core');
        $downloadPath = sprintf('%sdrupal-%s', $projectPath, $version);
        $copyPath = sprintf('%s%s', $projectPath, $directory);

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
        if (!$directory) {
            $directory = $io->ask(
                $this->trans('commands.site.new.questions.directory')
            );
            $input->setArgument('directory', $directory);
        }

        $version = $input->getArgument('version');
        if (!$version) {
            $version = $this->releasesQuestion($io, 'drupal');
            $input->setArgument('version', $version);
        }
    }
}
