<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\Debugommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;

class DebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('theme:debug')
            ->setDescription($this->trans('commands.theme.debug.description'))
            ->addArgument('theme', InputArgument::OPTIONAL, $this->trans('commands.theme.debug.options.theme'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');

        $table = $this->getTableHelper();
        $table->setlayout($table::LAYOUT_COMPACT);
        if ($theme) {
            $this->getTheme($theme, $output, $table);
        } else {
            $this->getAllThemes($output, $table);
        }
    }

    protected function getAllThemes($output, $table)
    {
        $table->setHeaders(
            [
                $this->trans('commands.theme.debug.messages.theme-id'),
                $this->trans('commands.theme.debug.messages.theme-name'),
                $this->trans('commands.theme.debug.messages.status'),
                $this->trans('commands.theme.debug.messages.version'),
            ]
        );

        $themes = $this->getThemeHandler()->rebuildThemeData();

        foreach ($themes as $themeId => $theme) {
            $status = $this->getThemeStatus($themeId);
            $table->addRow([$themeId, $theme->info['name'], $status, $theme->info['version']]);
        }
        $table->render($output);
    }

    protected function getTheme($themeId, $output, $table)
    {
        $theme = null;
        $message = $this->getMessageHelper();
        $themes = $this->getThemeHandler()->rebuildThemeData();

        if (isset($themes[$themeId])) {
            $theme = $themes[$themeId];
        } else {
            foreach ($themes as $themeAvailableId => $themeAvailable) {
                if ($themeAvailable->info['name'] == $themeId) {
                    $themeId = $themeAvailableId;
                    $theme = $themeAvailable;
                    break;
                }
            }
        }

        if ($theme) {
            $theme = $themes[$themeId];
            $status = $this->getThemeStatus($themeId);

            $table->setHeaders(
                [
                    $this->trans('commands.theme.debug.messages.theme-id'),
                    $this->trans('commands.theme.debug.messages.theme-properties'),
                ]
            );

            $table->addRow(['<info>' . $theme->info['name'] . '</info>']);
            $table->addRow(
                [
                    ' <comment>+ ' . $this->trans('commands.theme.debug.messages.status') . '</comment>',
                    $status,
                ]
            );

            $table->addRow(
                [
                    ' <comment>+ ' . $this->trans('commands.theme.debug.messages.version') . '</comment>',
                    $theme->info['version'],
                ]
            );

            $table->addRow([' <comment>+ ' . $this->trans('commands.theme.debug.messages.regions') . '</comment>']);
            $table = $this->addThemeAttributes($theme->info['regions'], $table);

            $table->render($output);
        } else {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.theme.debug.messages.invalid-theme'),
                    $themeId
                )
            );
        }
    }

    protected function getThemeStatus($theme)
    {
        $configFactory = $this->getConfigFactory();
        $defaultTheme = $configFactory->get('system.theme')->get('default');

        $status = ($theme->status)?$this->trans('commands.theme.debug.messages.installed'):$this->trans('commands.theme.debug.messages.uninstalled');
        if ($defaultTheme == $theme) {
            $status = $this->trans('commands.theme.debug.messages.default-theme');
        }

        return $status;
    }

    protected function addThemeAttributes($attr, $table)
    {
        foreach ($attr as $key => $value) {
            if (is_array($value)) {
                $table = $this->addThemeAttributes($value, $table);
            } else {
                $table->addRow(['  <comment>- </comment>'.$key, $value]);
            }
        }

        return $table;
    }
}
