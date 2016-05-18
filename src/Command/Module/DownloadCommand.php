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
use Drupal\Console\Command\ProjectDownloadTrait;
use Drupal\Console\Command\PHPProcessTrait;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;
    use PHPProcessTrait;

    protected $stable = true;

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
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.composer')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $composer = $input->getOption('composer');
        $module = $input->getArgument('module');

        if (!$module) {
            $module = $this->modulesQuestion($io);
            $input->setArgument('module', $module);
        }

        if (!$composer) {
            $path = $input->getOption('path');
            if (!$path) {
                $path = $io->ask(
                    $this->trans('commands.module.download.questions.path'),
                    'modules/contrib'
                );
                $input->setOption('path', $path);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * --latest option works but it's not recommended
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $modules = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $path = $input->getOption('path');
        $composer = $input->getOption('composer');

        if ($composer) {
            foreach ($modules as $module) {
                $this->stable
                = ('yes' != $io->ask(
                    $this->trans('commands.site.new.questions.stable'),
                    'yes'
                ))?
                false
                : true
                ;

                if (!$latest) {
                    $versions
                    = $this
                        ->getDrupalApi()
                        ->getPackagistModuleReleases($module, 10, $this->stable);

                    if (!$versions) {
                        $io->error(
                            sprintf(
                                $this->trans(
                                    'commands.module.download.messages.no-releases'
                                ),
                                $module
                            )
                        );
                        return;
                    } else {
                        $version
                        = $io->choice(
                            $this->trans('commands.site.new.questions.composer-release'),
                            $versions
                        );
                    }
                } else {
                    $versions
                    = $this
                        ->getDrupalApi()
                        ->getPackagistModuleReleases($module, 10, $this->stable);

                    if (!$versions) {
                        $io->error(
                            sprintf(
                                $this->trans(
                                    'commands.module.download.messages.no-releases'
                                ),
                                $module
                            )
                        );
                        return;
                    } else {
                        $version
                        = current(
                            $this
                                ->getDrupalApi()
                                ->getPackagistModuleReleases($module, 1, $this->stable)
                        );
                    }
                }

                $this->setComposerRepositories($io);

                $cmd = "cd " . $this->getApplication()->getSite()->getSiteRoot() . "; ";
                $cmd .= 'composer require "drupal/' . $module .':' . $version . '"';

                if ($this->execProcess($cmd)) {
                    $io->success(
                        sprintf(
                            $this->trans('commands.module.install.messages.composer'),
                            $version
                        )
                    );
                }
            }
        } else {
            $this->downloadModules($io, $modules, $latest, $path);
        }

        return true;
    }
}
