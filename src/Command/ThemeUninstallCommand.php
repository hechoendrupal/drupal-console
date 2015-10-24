<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ModuleInstallCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Core\Config\PreExistingConfigException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ThemeUninstallCommand extends ContainerAwareCommand
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
        $theme = $input->getArgument('theme');

        if (!$theme) {
            $theme_list = [];

            $dialog = $this->getDialogHelper();

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

            $output->writeln('[+] <info>'.$this->trans('commands.theme.uninstall.messages.installed-themes').'</info>');

            while (true) {
                $theme_name = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.theme.uninstall.questions.theme'), ''),
                    function ($theme_id) use ($theme_list) {
                        if ($theme_id == '' || $theme_list[$theme_id]) {
                            return $theme_id;
                        } else {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.theme.uninstall.questions.invalid-theme'), $theme_id)
                            );
                        }
                    },
                    false,
                    '',
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
        $configFactory = $this->getConfigFactory();
        $config = $configFactory->getEditable('system.theme');

        $themeHandler = $this->getThemeHandler();
        $themeHandler->refreshInfo();
        $theme = $input->getArgument('theme');

        $themes  = $themeHandler->rebuildThemeData();
        $themesAvailable = [];
        $themesInstalled = [];
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
                        $output->writeln(
                            '[+] <error>' .
                            sprintf(
                                $this->trans('commands.theme.uninstall.messages.error-default-theme'),
                                implode(',', $themesAvailable)
                            )
                            . '</error>'
                        );

                        return;
                    }

                    if ($themeKey === $config->get('admin')) {
                        $output->writeln(
                            '[+] <error>' .
                            sprintf(
                                $this->trans('commands.theme.uninstall.messages.error-admin-theme'),
                                implode(',', $themesAvailable)
                            )
                            . '</error>'
                        );
                        return;
                    }
                }

                $themeHandler->uninstall($theme);

                if (count($themesAvailable) > 1) {
                    $output->writeln(
                        '[+] <info>' .
                        sprintf(
                            $this->trans('commands.theme.uninstall.messages.themes-success'),
                            implode(',', $themesAvailable)
                        )
                        . '</info>'
                    );
                } else {
                    $output->writeln(
                        '[+] <info>' .
                        sprintf(
                            $this->trans('commands.theme.uninstall.messages.theme-success'),
                            array_shift($themesAvailable)
                        )
                        . '</info>'
                    );
                }
            } catch (UnmetDependenciesException $e) {
                $output->writeln(
                    '[+] <error>'.
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.dependencies'),
                        $e->getMessage()
                    )
                    .'</error>'
                );
                drupal_set_message($e->getTranslatedMessage($this->getStringTranslation(), $theme), 'error');
            }
        } elseif (empty($themesAvailable) && count($themesUninstalled) > 0) {
            if (count($themesUninstalled) > 1) {
                $output->writeln(
                    '[-] <info>' .
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.themes-nothing'),
                        implode(',', $themesUninstalled)
                    )
                    . '</info>'
                );
            } else {
                $output->writeln(
                    '[-] <info>' .
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.theme-nothing'),
                        implode(',', $themesUninstalled)
                    )
                    . '</info>'
                );
            }
        } else {
            if (count($themesUnavailable) > 1) {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.themes-missing'),
                        implode(',', $themesUnavailable)
                    )
                    . '</error>'
                );
            } else {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.theme.uninstall.messages.theme-missing'),
                        implode(',', $themesUnavailable)
                    )
                    . '</error>'
                );
            }
        }

        // Run cache rebuild to see changes in Web UI
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
