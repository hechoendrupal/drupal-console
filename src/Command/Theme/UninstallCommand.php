<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\UninstallCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Config\UnmetDependenciesException;

class UninstallCommand extends ThemeBaseCommand
{
    protected function configure()
    {
        $this
            ->setName('theme:uninstall')
            ->setDescription($this->trans('commands.theme.uninstall.description'))
            ->addArgument(
                'theme',
                InputArgument::IS_ARRAY,
                $this->trans('commands.theme.uninstall.options.theme')
            )
            ->setAliases(['thu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $titleTranslatableString = 'commands.theme.uninstall.messages.installed-themes';
        $questionTranslatableString = 'commands.theme.uninstall.questions.theme';
        $autocompleteAvailableThemes = $this->getAutoCompleteList(0);
        $this->getThemeArgument($titleTranslatableString, $questionTranslatableString, $autocompleteAvailableThemes);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->configFactory->getEditable('system.theme');
        $this->themeHandler->refreshInfo();
        $theme = $input->getArgument('theme');
        $this->prepareThemesArrays($theme);

        if (count($this->getAvailableThemes()) > 0) {
            try {
                foreach ($this->getAvailableThemes() as $themeKey => $themeName) {
                    if ($themeKey === $config->get('default')) {
                        $this->setInfoMessage('commands.theme.uninstall.messages.error-default-theme', $this->getAvailableThemes());
                        return 1;
                    }

                    if ($themeKey === $config->get('admin')) {
                        $this->setErrorMessage('commands.theme.uninstall.messages.error-admin-theme', $this->getAvailableThemes());
                        return 1;
                    }
                }

                $this->themeHandler->uninstall($theme);

                if (count($this->getAvailableThemes()) > 1) {
                    $this->setInfoMessage('commands.theme.uninstall.messages.themes-success', $this->getAvailableThemes());
                } else {
                    $this->setInfoMessage('commands.theme.uninstall.messages.theme-success', array_shift($this->getAvailableThemes()));
                }
            } catch (UnmetDependenciesException $e) {
                $this->setErrorMessage('commands.theme.uninstall.messages.dependencies', $this->getMessage());
                return 1;
            }
        } elseif (empty($this->getAvailableThemes()) && count($this->getUninstalledThemes()) > 0) {
            if (count($this->getUninstalledThemes()) > 1) {
                $this->setInfoMessage('commands.theme.uninstall.messages.themes-nothing', $this->getUninstalledThemes());
            } else {
                $this->setInfoMessage('commands.theme.uninstall.messages.theme-nothing', $this->getUninstalledThemes());
            }
        } else {
            if (count($this->getUnavailableThemes()) > 1) {
                $this->setErrorMessage('commands.theme.uninstall.messages.themes-missing', $this->getUnavailableThemes());
            } else {
                $this->setErrorMessage('commands.theme.uninstall.messages.theme-missing', $this->getUnavailableThemes());
            }
        }

        // Run cache rebuild to see changes in Web UI
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

        return 0;
    }
}
