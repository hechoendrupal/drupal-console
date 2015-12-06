<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\DebugCommand.
 */

namespace Drupal\Console\Command\Views;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drupal\views\Entity\View;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;

class DebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('views:debug')
            ->setDescription($this->trans('commands.views.debug.description'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-id')
            )
            ->addOption(
                'tag',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-tag')
            )->addOption(
                'status',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-status')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $view_id = $input->getArgument('view-id');
        $view_tag = $input->getOption('tag');
        $view_status = $input->getOption('status');

        if ($view_status == $this->trans('commands.common.status.enabled')) {
            $view_status = 1;
        } elseif ($view_status == $this->trans('commands.common.status.disabled')) {
            $view_status = 0;
        } else {
            $view_status = -1;
        }
        $table = new Table($output);
        $table->setStyle('compact');

        if ($view_id) {
            $this->geViewByID($output, $table, $view_id);
        } else {
            $this->getAllViews($view_tag, $view_status, $output, $table);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $resource_id    String
     */
    private function geViewByID($output, $table, $view_id)
    {
        $entity_manager = $this->getEntityManager();
        $view = $entity_manager->getStorage('view')->load($view_id);

        if (empty($view)) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.views.debug.messages.not-found'),
                    $view_id
                ).'</error>'
            );

            return false;
        }

        $configuration = array();
        $configuration[$this->trans('commands.views.debug.messages.view-id')] = $view->get('id');
        $configuration[$this->trans('commands.views.debug.messages.view-name')] = (string) $view->get('label');
        $configuration[$this->trans('commands.views.debug.messages.tag')] = $view->get('tag');
        $configuration[$this->trans('commands.views.debug.messages.status')] = $view->status() ? $this->trans('commands.common.status.enabled') : $this->trans('commands.common.status.disabled');
        $configuration[$this->trans('commands.views.debug.messages.description')] = $view->get('description');

        $configurationEncoded = Yaml::encode($configuration);

        $output->writeln($configurationEncoded);

        $table->render();

        $table->setHeaders(
            [
            $this->trans('commands.views.debug.messages.display-id'),
            $this->trans('commands.views.debug.messages.display-name'),
            $this->trans('commands.views.debug.messages.display-description'),
            $this->trans('commands.views.debug.messages.display-paths'),
            ]
        );

        $displays = $this->getDisplaysList($view);

        $output->writeln(
            '<info>'.sprintf(
                $this->trans('commands.views.debug.messages.display-list'),
                $view_id
            ).'</info>'
        );

        foreach ($displays as $display_id => $display) {
            $table->addRow(
                [
                $display_id,
                $display['name'],
                $display['description'],
                $this->getDisplayPaths($view, $display_id),
                ]
            );
        }

        $table->render();
    }

    protected function getAllViews($tag, $status, $output, $table)
    {
        $entity_manager = $this->getEntityManager();
        $views = $entity_manager->getStorage('view')->loadMultiple();

        $table->setHeaders(
            [
              $this->trans('commands.views.debug.messages.view-id'),
              $this->trans('commands.views.debug.messages.view-name'),
              $this->trans('commands.views.debug.messages.tag'),
              $this->trans('commands.views.debug.messages.status'),
              $this->trans('commands.views.debug.messages.path'),
            ]
        );

        $table->setStyle('compact');

        foreach ($views as $view) {
            if ($status != -1 && $view->status() != $status) {
                continue;
            }

            if (isset($tag) && $view->get('tag') != $tag) {
                continue;
            }
            $table->addRow(
                [
                $view->get('id'),
                $view->get('label'),
                $view->get('tag'),
                $view->status() ? $this->trans('commands.common.status.enabled') : $this->trans('commands.common.status.disabled'),
                $this->getDisplayPaths($view),
                ]
            );
        }
        $table->render();
    }

    /**
     * Gets a list of paths assigned to the view.
     *
     * @param \Drupal\views\Entity\View $view
     *      The view entity.
     *
     * @return array
     *      An array of paths for this view.
     */
    protected function getDisplayPaths(View $view, $display_id = null)
    {
        $all_paths = array();
        $executable = $view->getExecutable();
        $executable->initDisplay();
        foreach ($executable->displayHandlers as $display) {
            if ($display->hasPath()) {
                $path = $display->getPath();
                if (strpos($path, '%') === false) {
                    //  @see Views should expect and store a leading /. See:
                    //  https://www.drupal.org/node/2423913
                    $all_paths[] = '/'.$path;
                } else {
                    $all_paths[] = '/'.$path;
                }

                if ($display_id !== null && $display_id == $display->getBaseId()) {
                    return '/'.$path;
                }
            }
        }

        return implode(', ', array_unique($all_paths));
    }

    /**
     * Gets a list of displays included in the view.
     *
     * @param \Drupal\Core\Entity\View $view
     *                                       The view entity instance to get a list of displays for.
     *
     * @return array
     *               An array of display types that this view includes.
     */
    protected function getDisplaysList(View $view)
    {
        $displayManager = $this->getViewDisplayManager();
        $displays = array();
        foreach ($view->get('display') as $display) {
            $definition = $displayManager->getDefinition($display['display_plugin']);
            if (!empty($definition['admin'])) {
                // Cast the admin label to a string since it is an object.
                $displays[$definition['id']]['name'] = (string) $definition['admin'];
                $displays[$definition['id']]['description'] = (string) $definition['help'];
            }
        }
        asort($displays);

        return $displays;
    }
}
