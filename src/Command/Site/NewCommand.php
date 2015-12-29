<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Site\NewCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
                'site-name',
                InputArgument::REQUIRED,
                $this->trans('commands.site.new.arguments.site-name')
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

        $siteName = $input->getArgument('site-name');
        $version = $input->getArgument('version');

        $this->downloadProject($io, $siteName, $version, 'core');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $version = $input->getArgument('version');

        if (!$version) {
            $version = $this->releasesQuestion($io, 'drupal');
            $input->setArgument('version', $version);
        }
    }
}
