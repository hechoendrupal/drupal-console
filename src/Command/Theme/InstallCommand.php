<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\InstallCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Console\Style\DrupalStyle;

class InstallCommand extends Command
{
    protected $moduleInstaller;
    use ContainerAwareCommandTrait;

    protected function configure()
    {
        $this
            ->setName('theme:install')
            ->setDescription($this->trans('commands.theme.install.description'))
            ->addArgument('theme', InputArgument::IS_ARRAY, $this->trans('commands.theme.install.options.module'))
            ->addOption(
                'set-default',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.theme.install.options.set-default')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $theme = $input->getArgument('theme');

        if (!$theme) {
            $theme_list = [];

            $themes = $this->getThemeHandler()->rebuildThemeData();

            foreach ($themes as $theme_id => $theme) {
                if (!empty($theme->info['hidden'])) {
                    continue;
                }

                if ($theme->status) {
                    continue;
                }

                $theme_list[$theme_id] = $theme->getName();
            }

            $io->info($this->trans('commands.theme.install.messages.disabled-themes'));

            while (true) {
                $theme_name = $io->choiceNoList(
                    $this->trans('commands.theme.install.questions.theme'),
                    array_keys($theme_list)
                );

                if (empty($theme_name)) {
                    break;
                }

                $theme_list_install[] = $theme_name;

                if (array_search($theme_name, $theme_list_install, true) >= 0) {
                    unset($theme_list[$theme_name]);
                }
            }

            $input->setArgument('theme', $theme_list_install);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configFactory = $this->getService('config.factory');

        $config = $configFactory->getEditable('system.theme');

        $themeHandler = $this->getService('theme_handler');
        $themeHandler->refreshInfo();
        $theme = $input->getArgument('theme');
        $default = $input->getOption('set-default');

        if ($default && count($theme) > 1) {
            $io->error($this->trans('commands.theme.install.messages.invalid-theme-default'));

            return;
        }


        $themes  = $themeHandler->rebuildThemeData();
        $themesAvailable = [];
        $themesInstalled = [];
        $themesUnavailable = [];

        foreach ($theme as $themeName) {
            if (isset($themes[$themeName]) && $themes[$themeName]->status == 0) {
                $themesAvailable[] = $themes[$themeName]->info['name'];
            } elseif (isset($themes[$themeName]) && $themes[$themeName]->status == 1) {
                $themesInstalled[] = $themes[$themeName]->info['name'];
            } else {
                $themesUnavailable[] = $themeName;
            }
        }

        if (count($themesAvailable) > 0) {
            try {
                if ($themeHandler->install($theme)) {
                    if (count($themesAvailable) > 1) {
                        $io->info(
                            sprintf(
                                $this->trans('commands.theme.install.messages.themes-success'),
                                implode(',', $themesAvailable)
                            )
                        );
                    } else {
                        if ($default) {
                            // Set the default theme.
                            $config->set('default', $theme[0])->save();
                            $io->info(
                                sprintf(
                                    $this->trans('commands.theme.install.messages.theme-default-success'),
                                    $themesAvailable[0]
                                )
                            );
                        } else {
                            $io->info(
                                sprintf(
                                    $this->trans('commands.theme.install.messages.theme-success'),
                                    $themesAvailable[0]
                                )
                            );
                        }
                    }
                }
            } catch (UnmetDependenciesException $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.theme.install.messages.success'),
                        $theme
                    )
                );
                drupal_set_message($e->getTranslatedMessage($this->getStringTranslation(), $theme), 'error');
            }
        } elseif (empty($themesAvailable) && count($themesInstalled) > 0) {
            if (count($themesInstalled) > 1) {
                $io->info(
                    sprintf(
                        $this->trans('commands.theme.install.messages.themes-nothing'),
                        implode(',', $themesInstalled)
                    )
                );
            } else {
                $io->info(
                    sprintf(
                        $this->trans('commands.theme.install.messages.theme-nothing'),
                        implode(',', $themesInstalled)
                    )
                );
            }
        } else {
            if (count($themesUnavailable) > 1) {
                $io->error(
                    sprintf(
                        $this->trans('commands.theme.install.messages.themes-missing'),
                        implode(',', $themesUnavailable)
                    )
                );
            } else {
                $io->error(
                    sprintf(
                        $this->trans('commands.theme.install.messages.theme-missing'),
                        implode(',', $themesUnavailable)
                    )
                );
            }
        }

        // Run cache rebuild to see changes in Web UI
        $this->get('chain_queue')->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
