<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ViewsCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\views\Entity\View;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class ViewsCommand
 *
 * @DrupalCommand(
 *     extension = "views",
 *     extensionType = "module"
 * )
 *
 * @package Drupal\Console\Command\Debug
 */
class ViewsCommand extends Command
{
    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;
    /**
     * @var PluginManagerInterface
     */
    protected $viewsDisplayManager;

    /**
     * DebugCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param PluginManagerInterface     $viewsDisplayManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, PluginManagerInterface $viewsDisplayManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->viewsDisplayManager = $viewsDisplayManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:views')
            ->setDescription($this->trans('commands.debug.views.description'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.views.arguments.view-id')
            )
            ->addOption(
                'tag',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.views.arguments.view-tag')
            )->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.views.arguments.view-status')
            )
            ->setAliases(['vde']);
    }

    /**
     * {@inheritdoc}
     */
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

        if ($view_id) {
            $this->viewDetail($view_id);
        } else {
            $this->viewList($view_tag, $view_status);
        }
    }


    /**
     * @param $view_id
     * @return bool
     */
    private function viewDetail($view_id)
    {
        $view = $this->entityTypeManager->getStorage('view')->load($view_id);

        if (empty($view)) {
            $this->getIo()->error(sprintf($this->trans('commands.debug.views.messages.not-found'), $view_id));

            return false;
        }

        $configuration = [];
        $configuration [] = [$this->trans('commands.debug.views.messages.view-id'), $view->get('id')];
        $configuration [] = [$this->trans('commands.debug.views.messages.view-name'), (string) $view->get('label')];
        $configuration [] = [$this->trans('commands.debug.views.messages.tag'), $view->get('tag')];
        $configuration [] = [$this->trans('commands.debug.views.messages.status'), $view->status() ? $this->trans('commands.common.status.enabled') : $this->trans('commands.common.status.disabled')];
        $configuration [] = [$this->trans('commands.debug.views.messages.description'), $view->get('description')];

        $this->getIo()->comment($view_id);

        $this->getIo()->table([], $configuration);

        $tableHeader = [
          $this->trans('commands.debug.views.messages.display-id'),
          $this->trans('commands.debug.views.messages.display-name'),
          $this->trans('commands.debug.views.messages.display-description'),
          $this->trans('commands.debug.views.messages.display-paths'),
        ];
        $displays = $this->viewDisplayList($view);

        $this->getIo()->info(sprintf($this->trans('commands.debug.views.messages.display-list'), $view_id));

        $tableRows = [];
        foreach ($displays as $display_id => $display) {
            $tableRows[] = [
              $display_id,
              $display['name'],
              $display['description'],
              $this->viewDisplayPaths($view, $display_id),
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows);
    }

    /**
     * @param $tag
     * @param $status
     */
    protected function viewList($tag, $status)
    {
        $views = $this->entityTypeManager->getStorage('view')->loadMultiple();

        $tableHeader = [
          $this->trans('commands.debug.views.messages.view-id'),
          $this->trans('commands.debug.views.messages.view-name'),
          $this->trans('commands.debug.views.messages.tag'),
          $this->trans('commands.debug.views.messages.status'),
          $this->trans('commands.debug.views.messages.path')
        ];

        $tableRows = [];
        foreach ($views as $view) {
            if ($status != -1 && $view->status() != $status) {
                continue;
            }

            if (isset($tag) && $view->get('tag') != $tag) {
                continue;
            }
            $tableRows[] = [
              $view->get('id'),
              $view->get('label'),
              $view->get('tag'),
              $view->status() ? $this->trans('commands.common.status.enabled') : $this->trans('commands.common.status.disabled'),
              $this->viewDisplayPaths($view),
            ];
        }
        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }


    /**
     * @param \Drupal\views\Entity\View $view
     * @param null                      $display_id
     * @return string
     */
    protected function viewDisplayPaths(View $view, $display_id = null)
    {
        $all_paths = [];
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
     * @param \Drupal\views\Entity\View $view
     * @return array
     */
    protected function viewDisplayList(View $view)
    {
        $displays = [];
        foreach ($view->get('display') as $display) {
            $definition = $this->viewsDisplayManager->getDefinition($display['display_plugin']);
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
