<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DownloadCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Style\DrupalStyle;

class DownloadCommand extends Command
{
    use CommandTrait;
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
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.composer')
            )
            ->addOption(
                'unstable',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.unstable')
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $modules = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $path = $input->getOption('path');
        $composer = $input->getOption('composer');
        $unstable = true;

        if ($composer) {
            foreach ($modules as $module) {
                if (!$latest) {
                    $versions = $this->getApplication()->getDrupalApi()
                        ->getPackagistModuleReleases($module, 10, $unstable);

                    if (!$versions) {
                        $io->error(
                            sprintf(
                                $this->trans(
                                    'commands.module.download.messages.no-releases'
                                ),
                                $module
                            )
                        );

                        return 1;
                    } else {
                        $version = $io->choice(
                            $this->trans('commands.site.new.questions.composer-release'),
                            $versions
                        );
                    }
                } else {
                    $versions = $this->getApplication()->getDrupalApi()
                        ->getPackagistModuleReleases($module, 10, $unstable);

                    if (!$versions) {
                        $io->error(
                            sprintf(
                                $this->trans(
                                    'commands.module.download.messages.no-releases'
                                ),
                                $module
                            )
                        );
                        return 1;
                    } else {
                        $version = current(
                            $this->getApplication()->getDrupalApi()
                                ->getPackagistModuleReleases($module, 1, $unstable)
                        );
                    }
                }

                $this->setComposerRepositories("default");
                $command = sprintf(
                    'composer require drupal/%s:%s --prefer-dist --optimize-autoloader --sort-packages --update-no-dev',
                    $module,
                    $version
                );

                $shellProcess = $this->get('shell_process');
                if ($shellProcess->exec($command, true)) {
                    $io->success(
                        sprintf(
                            $this->trans('commands.module.download.messages.composer'),
                            $module
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
