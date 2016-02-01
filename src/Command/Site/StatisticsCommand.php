<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\StatisticsCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class StatisticsCommand
 * @package Drupal\Console\Command\Site
 */
class StatisticsCommand extends ContainerAwareCommand
{
    /**
     * @{@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('site:statistics')
            ->setDescription($this->trans('commands.site.statistics.description'))
            ->setHelp($this->trans('commands.site.statistics.help'));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $statistics = $this->getNodeCount();
        $statistics[$this->trans('commands.site.statistics.messages.comments')] = $this->getCommentCount();
        $statistics[$this->trans('commands.site.statistics.messages.vocabulary')] = $this->getTaxonomyVocabularyCount();
        $statistics[$this->trans('commands.site.statistics.messages.taxonomy-terms')] = $this->getTaxonomyTermCount();
        $statistics[$this->trans('commands.site.statistics.messages.files')] = $this->getFileCount();
        $statistics[$this->trans('commands.site.statistics.messages.users')] = $this->getUserCount();
        $statistics[$this->trans('commands.site.statistics.messages.modules-enabled')] = $this->getModuleCount(true);
        $statistics[$this->trans('commands.site.statistics.messages.modules-disabled')] = $this->getModuleCount(false);
        $statistics[$this->trans('commands.site.statistics.messages.themes-enabled')] = $this->getThemeCount(true);
        $statistics[$this->trans('commands.site.statistics.messages.themes-disabled')] = $this->getThemeCount(false);

        $this->statisticsList($io, $statistics);
    }

    /**
     * @return mixed
     */
    private function getNodeCount()
    {
        $nodes = [];
        $entityQuery = $this->getEntityQuery()->get('node_type');
        $nodeTypes = $entityQuery->execute();

        foreach ($nodeTypes as $nodeType) {
            $nodesPerType = $this->getEntityQuery()->get('node')->condition('type', $nodeType)->count()->execute();
            $key = sprintf(
                $this->trans('commands.site.statistics.messages.node-type'),
                $nodeType
            );
            $nodes[$key] = $nodesPerType;
        }

        return $nodes;
    }

    /**
     * @return mixed
     */
    private function getCommentCount()
    {
        $entityQuery = $this->getEntityQuery()->get('comment')->count();
        $comments = $entityQuery->execute();

        return $comments;
    }

    /**
     * @return mixed
     */
    private function getTaxonomyVocabularyCount()
    {
        $entityQuery = $this->getEntityQuery()->get('taxonomy_vocabulary')->count();
        $vocabularies = $entityQuery->execute();

        return $vocabularies;
    }

    /**
     * @return mixed
     */
    private function getTaxonomyTermCount()
    {
        $entityQuery = $this->getEntityQuery()->get('taxonomy_term')->count();
        $terms = $entityQuery->execute();

        return $terms;
    }

    /**
     * @return mixed
     */
    private function getFileCount()
    {
        $entityQuery = $this->getEntityQuery()->get('file')->count();
        $files = $entityQuery->execute();

        return $files;
    }

    /**
     * @return mixed
     */
    private function getUserCount()
    {
        $entityQuery = $this->getEntityQuery()->get('user')->count();
        $users = $entityQuery->execute();

        return $users;
    }

    /**
     * @param bool|TRUE $status
     * @return int
     */
    private function getModuleCount($status = true)
    {
        $modules = system_rebuild_module_data();
        $moduleCount = 0;
        foreach ($modules as $module_id => $module) {
            if ($module->status == $status) {
                $moduleCount++;
            }
        }

        return $moduleCount;
    }

    /**
     * @param bool|TRUE $status
     * @return int
     */
    private function getThemeCount($status = true)
    {
        $themes = $this->getThemeHandler()->rebuildThemeData();
        $themeCount =0;
        foreach ($themes as $themeId => $theme) {
            if ($theme->status == $status) {
                $themeCount++;
            }
        }

        return $themeCount;
    }

    /**
     * @param DrupalStyle $io
     * @param mixed       $statistics
     */
    private function statisticsList(DrupalStyle $io, $statistics)
    {
        $tableHeader =[
            $this->trans('commands.site.statistics.messages.stat-name'),
            $this->trans('commands.site.statistics.messages.stat-quantity'),
        ];

        $tableRows = [];
        foreach ($statistics as $type => $amount) {
            $tableRows[] = [
              $type,
              $amount
            ];
        }

        $io->table($tableHeader, $tableRows);
    }
}
