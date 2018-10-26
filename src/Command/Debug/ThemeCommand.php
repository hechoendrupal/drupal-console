<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ThemeCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ThemeHandler;

class ThemeCommand extends Command
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * DebugCommand constructor.
     *
     * @param ConfigFactory $configFactory
     * @param ThemeHandler  $themeHandler
     */
    public function __construct(
        ConfigFactory $configFactory,
        ThemeHandler $themeHandler
    ) {
        $this->configFactory = $configFactory;
        $this->themeHandler = $themeHandler;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('debug:theme')
            ->setDescription($this->trans('commands.debug.theme.description'))
            ->addArgument(
                'theme',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.theme.arguments.theme')
            )
            ->setAliases(['dt']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');
        if ($theme) {
            $this->themeDetail($theme);
        } else {
            $this->themeList();
        }
    }

    protected function themeList()
    {
        $tableHeader = [
            $this->trans('commands.debug.theme.messages.theme-id'),
            $this->trans('commands.debug.theme.messages.theme-name'),
            $this->trans('commands.debug.theme.messages.status'),
            $this->trans('commands.debug.theme.messages.version'),
        ];

        $themes = $this->themeHandler->rebuildThemeData();
        $tableRows = [];
        foreach ($themes as $themeId => $theme) {
            $status = $this->getThemeStatus($theme);
            $tableRows[] = [
                $themeId,
                $theme->info['name'],
                $status,
                (isset($theme->info['version'])) ? $theme->info['version'] : '',
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows);
    }

    protected function themeDetail($themeId)
    {
        $theme = null;
        $themes = $this->themeHandler->rebuildThemeData();

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

            $this->getIo()->info($theme->info['name']);

            $this->getIo()->comment(
                sprintf(
                    '%s : ',
                    $this->trans('commands.debug.theme.messages.status')
                ),
                false
            );
            $this->getIo()->writeln($status);
            $this->getIo()->comment(
                sprintf(
                    '%s : ',
                    $this->trans('commands.debug.theme.messages.version')
                ),
                false
            );
            $this->getIo()->writeln($theme->info['version']);
            $this->getIo()->comment($this->trans('commands.debug.theme.messages.regions'));
            $tableRows = $this->addThemeAttributes($theme->info['regions'], $tableRows);
            $this->getIo()->table([], $tableRows);
        } else {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.debug.theme.messages.invalid-theme'),
                    $themeId
                )
            );
        }
    }

    protected function getThemeStatus($theme)
    {
        $defaultTheme = $this->configFactory->get('system.theme')->get('default');

        $status = ($theme->status)?$this->trans('commands.debug.theme.messages.installed'):$this->trans('commands.debug.theme.messages.uninstalled');
        if ($defaultTheme == $theme) {
            $status = $this->trans('commands.debug.theme.messages.default-theme');
        }

        return $status;
    }

    protected function addThemeAttributes($attr, $tableRows = [])
    {
        foreach ($attr as $key => $value) {
            if (is_array($value)) {
                $tableRows = $this->addThemeAttributes($value, $tableRows);
            } else {
                $tableRows[] = [
                    $key,
                    $value,
                ];
            }
        }

        return $tableRows;
    }
}
