<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\StatisticsCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Console\Extension\Manager;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class StatisticsCommand
 * @package Drupal\Console\Command\Site
 */
class StatisticsCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * @var QueryFactory
     */
    protected $entityQuery;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * StatisticsCommand constructor.
     * @param DrupalApi              $drupalApi
     * @param QueryFactory           $entityQuery;
     * @param Manager                $extensionManager
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(
        DrupalApi $drupalApi,
        QueryFactory $entityQuery,
        Manager $extensionManager,
        ModuleHandlerInterface $moduleHandler
    ) {
        $this->drupalApi = $drupalApi;
        $this->entityQuery = $entityQuery;
        $this->extensionManager = $extensionManager;
        $this->moduleHandler = $moduleHandler;
        parent::__construct();
    }

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

        $bundles = $this->drupalApi->getBundles();
        foreach ($bundles as $bundleType => $bundleName) {
            $key = sprintf(
                $this->trans('commands.site.statistics.messages.node-type'),
                $bundleName
            );
            $statistics[$key] = $this->getNodeTypeCount($bundleType);
        }
        $statistics[$this->trans('commands.site.statistics.messages.comments')] = $this->getCommentCount();
        $statistics[$this->trans('commands.site.statistics.messages.vocabulary')] = $this->getTaxonomyVocabularyCount();
        $statistics[$this->trans('commands.site.statistics.messages.taxonomy-terms')] = $this->getTaxonomyTermCount();
        $statistics[$this->trans('commands.site.statistics.messages.files')] = $this->getFileCount();
        $statistics[$this->trans('commands.site.statistics.messages.users')] = $this->getUserCount();
        $statistics[$this->trans('commands.site.statistics.messages.views')] = $this->getViewCount();
        $statistics[$this->trans('commands.site.statistics.messages.modules-enabled')] = $this->getModuleCount(true);
        $statistics[$this->trans('commands.site.statistics.messages.modules-disabled')] = $this->getModuleCount(false);
        $statistics[$this->trans('commands.site.statistics.messages.themes-enabled')] = $this->getThemeCount(true);
        $statistics[$this->trans('commands.site.statistics.messages.themes-disabled')] = $this->getThemeCount(false);

        $this->statisticsList($io, $statistics);
    }


    /**
     * @param $nodeType
     * @return mixed
     */
    private function getNodeTypeCount($nodeType)
    {
        $nodesPerType = $this->entityQuery->get('node')->condition('type', $nodeType)->count();
        $nodes = $nodesPerType->execute();

        return $nodes;
    }

    /**
     * @return mixed
     */
    private function getCommentCount()
    {
        if (!$this->moduleHandler->moduleExists('comment')) {
            return 0;
        }
        
        $entityQuery = $this->entityQuery->get('comment')->count();
        $comments = $entityQuery->execute();

        return $comments;
    }

    /**
     * @return mixed
     */
    private function getTaxonomyVocabularyCount()
    {
        $entityQuery = $this->entityQuery->get('taxonomy_vocabulary')->count();
        $vocabularies = $entityQuery->execute();

        return $vocabularies;
    }

    /**
     * @return mixed
     */
    private function getTaxonomyTermCount()
    {
        $entityQuery = $this->entityQuery->get('taxonomy_term')->count();
        $terms = $entityQuery->execute();

        return $terms;
    }

    /**
     * @return mixed
     */
    private function getFileCount()
    {
        $entityQuery = $this->entityQuery->get('file')->count();
        $files = $entityQuery->execute();

        return $files;
    }

    /**
     * @return mixed
     */
    private function getUserCount()
    {
        $entityQuery = $this->entityQuery->get('user')->count();
        $users = $entityQuery->execute();

        return $users;
    }

    /**
     * @param bool|TRUE $status
     * @return int
     */
    private function getModuleCount($status = true)
    {
        if ($status) {
            return count($this->extensionManager->discoverModules()->showCore()->showNoCore()->showInstalled()->getList());
        }

        return count($this->extensionManager->discoverModules()->showCore()->showNoCore()->showUninstalled()->getList());
    }

    /**
     * @param bool|TRUE $status
     * @return int
     */
    private function getThemeCount($status = true)
    {
        if ($status) {
            return count($this->extensionManager->discoverThemes()->showCore()->showNoCore()->showInstalled()->getList());
        }

        return count($this->extensionManager->discoverThemes()->showCore()->showNoCore()->showUninstalled()->getList());
    }

    /**
     * @return mixed
     */
    private function getViewCount($status = true, $tag = 'default')
    {
        $entityQuery = $this->entityQuery->get('view')->condition('tag', 'default', '<>')->count();
        $views = $entityQuery->execute();

        return $views;
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
