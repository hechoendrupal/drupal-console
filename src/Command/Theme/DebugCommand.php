<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\Debugommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends Command
{
    use CommandTrait;

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
            ->setName('theme:debug')
            ->setDescription($this->trans('commands.theme.debug.description'))
            ->addArgument('theme', InputArgument::OPTIONAL, $this->trans('commands.theme.debug.arguments.theme'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $theme = $input->getArgument('theme');
        if ($theme) {
            $this->themeDetail($io, $theme);
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

        $themes = $this->themeHandler->rebuildThemeData();
        $tableRows = [];
        foreach ($themes as $themeId => $theme) {
            $status = $this->getThemeStatus($theme);
            $tableRows[] = [
                $themeId, $theme->info['name'],
                $status, $theme->info['version'],
            ];
        }

        $io->table($tableHeader, $tableRows);
    }

    protected function themeDetail(DrupalStyle $io, $themeId)
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

            $io->info($theme->info['name']);

            $io->comment(
                sprintf(
                    '%s : ',
                    $this->trans('commands.theme.debug.messages.status')
                ),
                false
            );
            $io->writeln($status);
            $io->comment(
                sprintf(
                    '%s : ',
                    $this->trans('commands.theme.debug.messages.version')
                ),
                false
            );
            $io->writeln($theme->info['version']);
            $io->comment($this->trans('commands.theme.debug.messages.regions'));
            $tableRows = $this->addThemeAttributes($theme->info['regions'], $tableRows);
            $io->table([], $tableRows);
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
        $defaultTheme = $this->configFactory->get('system.theme')->get('default');

        $status = ($theme->status)?$this->trans('commands.theme.debug.messages.installed'):$this->trans('commands.theme.debug.messages.uninstalled');
        if ($defaultTheme == $theme) {
            $status = $this->trans('commands.theme.debug.messages.default-theme');
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
