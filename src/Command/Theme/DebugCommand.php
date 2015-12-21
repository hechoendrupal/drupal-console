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
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('theme:debug')
            ->setDescription($this->trans('commands.theme.debug.description'))
            ->addArgument('theme', InputArgument::OPTIONAL, $this->trans('commands.theme.debug.arguments.theme'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $theme = $input->getArgument('theme');
        if ($theme) {
            $this->themeDetail($theme, $io);
        } else {
            $this->themeList($io);
        }
    }

    protected function themeList(DrupalStyle $io)
    {
        $tableHeader = [
        $this->trans('commands.theme.debug.messages.theme-id'),
        $this->trans('commands.theme.debug.messages.theme-name'),
        $this->trans('commands.theme.debug.messages.status'),
        $this->trans('commands.theme.debug.messages.version'),
        ];

        $themes = $this->getThemeHandler()->rebuildThemeData();
        $tableRows = [];
        foreach ($themes as $themeId => $theme) {
            $status = $this->getThemeStatus($themeId);
            $tableRows[] = [
            $themeId, $theme->info['name'],
            $status, $theme->info['version'],
            ];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }

    protected function themeDetail(DrupalStyle $io, $themeId)
    {
        $theme = null;
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

            $tableHeader = [
            $this->trans('commands.theme.debug.messages.theme-id'),
            $this->trans('commands.theme.debug.messages.theme-properties'),
            ];
            $tableRows = [
            [
            '<info>' . $theme->info['name'] . '</info>',
            ],
            [
            ' <comment>+ ' . $this->trans('commands.theme.debug.messages.status') . '</comment>',
            $status,
            ],
            [
            ' <comment>+ ' . $this->trans('commands.theme.debug.messages.version') . '</comment>',
            $theme->info['version'],
            ],
            [
            ' <comment>+ ' . $this->trans('commands.theme.debug.messages.regions') . '</comment>',
            ]
            ];
            $tableRows = $this->addThemeAttributes($theme->info['regions'], $tableRows);

            $io->table($tableHeader, $tableRows, 'compact');
        } else {
            $io->error(
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

    protected function addThemeAttributes($attr, $tableRows)
    {
        foreach ($attr as $key => $value) {
            if (is_array($value)) {
                $tableRows = $this->addThemeAttributes($value, $tableRows);
            } else {
                $tableRows[] = [
                '  <comment>- </comment>'.$key,
                $value,
                ];
            }
        }

        return $tableRows;
    }
}
