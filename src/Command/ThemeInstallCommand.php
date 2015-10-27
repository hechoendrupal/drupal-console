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

class ThemeInstallCommand extends ContainerAwareCommand
{
    protected $moduleInstaller;

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
        $theme = $input->getArgument('theme');

        if (!$theme) {
            $theme_list = [];

            $dialog = $this->getDialogHelper();

            $themes = $this->getThemeHandler()->rebuildThemeData();

            foreach ($themes as $theme_id => $theme) {
                if (!empty($theme->info['hidden'])) {
                    continue;
                }

                if (!empty($theme->status == 1)) {
                    continue;
                }
                $theme_list[$theme_id] = $theme->getName();
            }

            $output->writeln('[+] <info>'.$this->trans('commands.theme.install.messages.disabled-themes').'</info>');

            while (true) {
                $theme_name = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.theme.install.questions.theme'), ''),
                    function ($theme_id) use ($theme_list) {
                        if ($theme_id == '' || $theme_list[$theme_id]) {
                            return $theme_id;
                        } else {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.theme.install.questions.invalid-theme'), $theme_id)
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
        $default = $input->getOption('set-default');

        if ($default && count($theme) > 1) {
            $output->writeln(
                '[-] <error>' . $this->trans('commands.theme.install.messages.invalid-theme-default')
                .'</error>'
            );

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
                        $output->writeln(
                            '[+] <info>' .
                            sprintf(
                                $this->trans('commands.theme.install.messages.themes-success'),
                                implode(',', $themesAvailable)
                            )
                            . '</info>'
                        );
                    } else {
                        if ($default) {
                            // Set the default theme.
                            $config->set('default', $theme[0])->save();
                            $output->writeln(
                                '[+] <info>' .
                                sprintf(
                                    $this->trans('commands.theme.install.messages.theme-default-success'),
                                    $themesAvailable[0]
                                )
                                . '</info>'
                            );
                        } else {
                            $output->writeln(
                                '[+] <info>' .
                                sprintf(
                                    $this->trans('commands.theme.install.messages.theme-success'),
                                    $themesAvailable[0]
                                )
                                . '</info>'
                            );
                        }
                    }
                }
            } catch (UnmetDependenciesException $e) {
                print 'error3';
                $output->writeln(
                    '[+] <error>'.
                    sprintf(
                        $this->trans('commands.theme.install.messages.success'),
                        $theme
                    )
                    .'</error>'
                );
                drupal_set_message($e->getTranslatedMessage($this->getStringTranslation(), $theme), 'error');
            }
        } elseif (empty($themesAvailable) && count($themesInstalled) > 0) {
            if (count($themesInstalled) > 1) {
                $output->writeln(
                    '[-] <info>' .
                    sprintf(
                        $this->trans('commands.theme.install.messages.themes-nothing'),
                        implode(',', $themesInstalled)
                    )
                    . '</info>'
                );
            } else {
                $output->writeln(
                    '[-] <info>' .
                    sprintf(
                        $this->trans('commands.theme.install.messages.theme-nothing'),
                        implode(',', $themesInstalled)
                    )
                    . '</info>'
                );
            }
        } else {
            if (count($themesUnavailable) > 1) {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.theme.install.messages.themes-missing'),
                        implode(',', $themesUnavailable)
                    )
                    . '</error>'
                );
            } else {
                $output->writeln(
                    '[-] <error>' .
                    sprintf(
                        $this->trans('commands.theme.install.messages.theme-missing'),
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
