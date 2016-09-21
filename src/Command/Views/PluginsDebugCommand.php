<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\PluginsDebugCommand.
 */

namespace Drupal\Console\Command\Views;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\views\Views;

/**
 * Class PluginsDebugCommand
 * @package Drupal\Console\Command\Views
 */
class PluginsDebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('views:plugins:debug')
            ->setDescription($this->trans('commands.views.plugins.debug.description'))
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.plugins.debug.arguments.type')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $type = $input->getArgument('type');

        $this->pluginList($io, $type);
    }

    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param $type
     */
    protected function pluginList(DrupalStyle $io, $type)
    {
        $plugins = Views::pluginList();

        $rows = [];
        foreach ($plugins as &$plugin) {
            if ($type && $plugin['type'] != $type) {
                continue;
            }

            $views = [];
            // Link each view name to the view itself.
            foreach ($plugin['views'] as $plugin_name => $view) {
                $views[] = $view;
            }
            $rows[] = [$plugin['type'], $plugin['title'], $plugin['provider'], implode(",", $views)];
        }

        // Sort rows by field name.
        ksort($rows);


        $tableHeader = [
          $this->trans('commands.views.plugins.debug.messages.type'),
          $this->trans('commands.views.plugins.debug.messages.name'),
          $this->trans('commands.views.plugins.debug.messages.provider'),
          $this->trans('commands.views.plugins.debug.messages.views'),
        ];

        $io->table($tableHeader, $rows, 'compact');
    }
}
