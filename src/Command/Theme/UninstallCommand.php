<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\UninstallCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Console\Style\DrupalStyle;

class UninstallCommand extends ContainerAwareCommand
{
    protected $moduleInstaller;

    protected function configure()
    {
        $this
            ->setName('theme:uninstall')
            ->setDescription($this->trans('commands.theme.uninstall.description'))
            ->addArgument('theme', InputArgument::IS_ARRAY, $this->trans('commands.theme.uninstall.options.module'));
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

                if (!empty($theme->status == 0)) {
                    continue;
                }
                $theme_list[$theme_id] = $theme->getName();
            }

            $io->info($this->trans('commands.theme.uninstall.messages.installed-themes'));

            while (true) {
                $theme_name = $io->choiceNoList(
                    $this->trans('commands.theme.uninstall.questions.theme'),
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

        $configFactory = $this->getConfigFactory();
        $config = $configFactory->getEditable('system.theme');

        $themeHandler = $this->getThemeHandler();
        $themeHandler->refreshInfo();
        $theme = $input->getArgument('theme');

        $themes  = $themeHandler->rebuildThemeData();
        $themesAvailable = [];
        $themesUninstalled = [];
        $themesUnavailable = [];

        foreach ($theme as $themeName) {
            if (isset($themes[$themeName]) && $themes[$themeName]->status == 1) {
                $themesAvailable[$themeName] = $themes[$themeName]->info['name'];
            } elseif (isset($themes[$themeName]) && $themes[$themeName]->status == 0) {
                $themesUninstalled[] = $themes[$themeName]->info['name'];
            } else {
                $themesUnavailable[] = $themeName;
            }
        }

        if (count($themesAvailable) > 0) {
            try {
                foreach ($themesAvailable as $themeKey => $themeName) {
                    if ($themeKey === $config->get('default')) {
                        $io->error(
                            sprintf(
                                $this->trans('commands.theme.uninstall.messages.error-default-theme'),
                                implode(',', $themesAvailable)
                            )
                        );

                        return;
                    }

                    if ($themeKey === $config->get('admin')) {
                        $io->error(
                            sprintf(
                                $this->trans('commands.theme.uninstall.messages.error-admin-theme'),
                                implode(',', $themesAvailable)
                            )
                        );
                        return;
                    }
                }

                $themeHandler->uninstall($theme);

                if (count($themesAvailable) > 1) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.theme.uninstall.messages.themes-success'),
                            implode(',', $themesAvailable)
                        )
                    );
                } else {
                    $io->info(
                        sprintf(
                            $this->trans('commands.theme.uninstall.messages.theme-success'),
                            array_shift($themesAvailable)
                        )
                    );
                }
            } catch (UnmetDependenciesException $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.dependencies'),
                        $e->getMessage()
                    )
                );
                drupal_set_message($e->getTranslatedMessage($this->getStringTranslation(), $theme), 'error');
            }
        } elseif (empty($themesAvailable) && count($themesUninstalled) > 0) {
            if (count($themesUninstalled) > 1) {
                $io->info(
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.themes-nothing'),
                        implode(',', $themesUninstalled)
                    )
                );
            } else {
                $io->info(
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.theme-nothing'),
                        implode(',', $themesUninstalled)
                    )
                );
            }
        } else {
            if (count($themesUnavailable) > 1) {
                $io->error(
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.themes-missing'),
                        implode(',', $themesUnavailable)
                    )
                );
            } else {
                $io->error(
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.theme-missing'),
                        implode(',', $themesUnavailable)
                    )
                );
            }
        }

        // Run cache rebuild to see changes in Web UI
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
