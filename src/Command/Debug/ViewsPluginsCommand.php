<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ViewsPluginsCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\views\Views;

/**
 * Class ViewsPluginsCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class ViewsPluginsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:views:plugins')
            ->setDescription($this->trans('commands.debug.views.plugins.description'))
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.views.plugins.arguments.type')
            )->setAliases(['dvp']);
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
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
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
          $this->trans('commands.debug.views.plugins.messages.type'),
          $this->trans('commands.debug.views.plugins.messages.name'),
          $this->trans('commands.debug.views.plugins.messages.provider'),
          $this->trans('commands.debug.views.plugins.messages.views'),
        ];

        $io->table($tableHeader, $rows, 'compact');
    }
}
