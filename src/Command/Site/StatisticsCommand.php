<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\StatisticsCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class SiteDebugCommand
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
        $assets = [];

        //$assets[$this->trans('commands.site.assets.messages.nodes')] =
        $this->getNodeCount($assets);
        $assets[$this->trans('commands.site.statistics.messages.comments')] = $this->getCommentCount();
        $assets[$this->trans('commands.site.statistics.messages.vocabulary')] = $this->getTaxonomyVocabularyCount();
        $assets[$this->trans('commands.site.statistics.messages.taxonomy-terms')] = $this->getTaxonomyTermCount();
        $assets[$this->trans('commands.site.statistics.messages.files')] = $this->getFileCount();
        $assets[$this->trans('commands.site.statistics.messages.users')] = $this->getUserCount();
        $assets[$this->trans('commands.site.statistics.messages.modules-enabled')] = $this->getModuleCount(true);
        $assets[$this->trans('commands.site.statistics.messages.modules-disabled')] = $this->getModuleCount(false);
        $assets[$this->trans('commands.site.statistics.messages.themes-enabled')] = $this->getThemeCount(true);
        $assets[$this->trans('commands.site.statistics.messages.themes-disabled')] = $this->getThemeCount(false);

        $this->assetsList($io, $assets);
    }

    /**
     */
    private function getNodeCount(&$assets)
    {
        //$entityQuery = $this->getEntityQuery()->get('node')->count();
        //$nodes = $entityQuery->execute();

        $entityQuery = $this->getEntityQuery()->get('node_type');
        $nodeTypes = $entityQuery->execute();

        foreach ($nodeTypes as $nodeType) {
            $nodesPerType = $this->getEntityQuery()->get('node')->condition('type', $nodeType)->count()->execute();
            $key = sprintf(
                $this->trans('commands.site.statistics.messages.node-type'),
                $nodeType
            );
            $assets[$key] = $nodesPerType;
        }

        return $nodesPerType;
    }



    /**
     */
    private function getCommentCount()
    {
        $entityQuery = $this->getEntityQuery()->get('comment')->count();
        $comments = $entityQuery->execute();

        return $comments;
    }


    /**
     */
    private function getTaxonomyVocabularyCount()
    {
        $entityQuery = $this->getEntityQuery()->get('taxonomy_vocabulary')->count();
        $vocabularies = $entityQuery->execute();

        return $vocabularies;
    }
    /**
     */
    private function getTaxonomyTermCount()
    {
        $entityQuery = $this->getEntityQuery()->get('taxonomy_term')->count();
        $terms = $entityQuery->execute();

        return $terms;
    }

    /**
     */
    private function getFileCount()
    {
        $entityQuery = $this->getEntityQuery()->get('file')->count();
        $files = $entityQuery->execute();

        return $files;
    }

    /**
     */
    private function getUserCount()
    {
        $entityQuery = $this->getEntityQuery()->get('user')->count();
        $users = $entityQuery->execute();

        return $users;
    }

    /**
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
     * @param mixes       $assets
     */
    private function assetsList(DrupalStyle $io, $assets)
    {
        $application = $this->getApplication();

        $tableHeader =[
            $this->trans('commands.site.statistics.messages.stat-name'),
            $this->trans('commands.site.statistics.messages.stat-quantity'),
        ];

        $tableRows = [];
        foreach ($assets as $type => $amount) {
            $tableRows[] = [
              $type,
              $amount
            ];
        }

        $io->table($tableHeader, $tableRows);
    }
}
