<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DownloadCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Functionality\ProjectDownloadTrait;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;

    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription($this->trans('commands.module.download.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.download.arguments.module')
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.download.options.path')
            )
            ->addOption(
                'latest',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.download.options.latest')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $this->modulesQuestion($io);
            $input->setArgument('module', $module);
        }

        $path = $input->getOption('path');
        if (!$path) {
            $path = $io->ask(
                $this->trans('commands.module.download.questions.path'),
                'modules/contrib'
            );
            $input->setOption('path', $path);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $path = $input->getOption('path');
        $this->downloadModules($io, $module, $latest, $path);

        return true;
    }
}
