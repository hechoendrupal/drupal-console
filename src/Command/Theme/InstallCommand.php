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
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;

class InstallCommand extends ThemeBaseCommand
{
    use ProjectDownloadTrait;
    use ModuleTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('theme:install')
            ->setDescription($this->trans('commands.theme.install.description'))
            ->addArgument(
                'theme',
                InputArgument::IS_ARRAY,
                $this->trans('commands.theme.install.options.theme')
            )
            ->addOption(
                'set-default',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.theme.install.options.set-default')
            )->setAliases(['thi']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $titleTranslatableString = 'commands.theme.install.messages.disabled-themes';
        $questionTranslatableString = 'commands.theme.install.questions.theme';
        $autocompleteAvailableThemes = $this->getAutoCompleteList();
        $this->getThemeArgument($titleTranslatableString, $questionTranslatableString, $autocompleteAvailableThemes);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->configFactory->getEditable('system.theme');

        $this->themeHandler->refreshInfo();
        $theme = $input->getArgument('theme');
        $default = $input->getOption('set-default');

        if ($default && count($theme) > 1) {
            $this->getIo()->error($this->trans('commands.theme.install.messages.invalid-theme-default'));

            return 1;
        }

        $this->prepareThemesArrays($theme);
        if (count($this->getUninstalledThemes()) > 0) {
            try {
                if ($this->themeHandler->install($theme)) {
                    if (count($this->getUninstalledThemes()) > 1) {
                        $this->setInfoMessage('commands.theme.install.messages.themes-success', $this->getUninstalledThemes());
                    } else {
                        if ($default) {
                            // Set the default theme.
                            $config->set('default', $theme[0])->save();
                            $this->setInfoMessage('commands.theme.install.messages.theme-default-success', array_shift($this->getUninstalledThemes()));
                        } else {
                            $this->setInfoMessage('commands.theme.install.messages.theme-success', array_shift($this->getUninstalledThemes()));
                        }
                    }
                }
            } catch (UnmetDependenciesException $e) {
                $this->setErrorMessage('commands.theme.install.messages.dependencies', $theme);
                return 1;
            }
        } elseif (empty($this->getUninstalledThemes()) && count($this->getAvailableThemes()) > 0) {
            if (count($this->getAvailableThemes()) > 1) {
                $this->setInfoMessage('commands.theme.install.messages.themes-nothing', $this->getAvailableThemes());
            } else {
                $this->setInfoMessage('commands.theme.install.messages.theme-nothing', $this->getAvailableThemes());
            }
        } else {
            if (count($this->getUnavailableThemes()) > 1) {
                $this->setErrorMessage('commands.theme.install.messages.themes-missing', $this->getUnavailableThemes());
            } else {
                $resultList = $this->downloadThemes($theme, $default);
            }
        }

        // Run cache rebuild to see changes in Web UI
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

        return 0;
    }
}
