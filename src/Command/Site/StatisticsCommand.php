<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\StatisticsCommand.
 */

namespace Drupal\Console\Command\Site;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StatisticsCommand
 *
 * @package Drupal\Console\Command\Site
 */
class StatisticsCommand extends Command
{
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
     *
     * @param DrupalApi $drupalApi
     * @param QueryFactory $entityQuery ;
     * @param Manager $extensionManager
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
            ->setHelp($this->trans('commands.site.statistics.help'))
            ->setAliases(['sst']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statistics = [];
        if ($this->moduleHandler->moduleExists('node')) {
            $bundles = $this->drupalApi->getBundles();
            foreach ($bundles as $bundleType => $bundleName) {
                $key = sprintf(
                    $this->trans('commands.site.statistics.messages.node-type'),
                    $bundleName
                );
                $statistics[$key] = $this->getEntitiesCount('node', [
                    'name' => 'type',
                    'value' => $bundleType,
                    'condition' => '=',
                ]);
            }
        }

        if ($this->moduleHandler->moduleExists('comment')) {
            $statistics[$this->trans('commands.site.statistics.messages.comments')] = $this->getEntitiesCount('comment');
        }

        if ($this->moduleHandler->moduleExists('taxonomy')) {
            $statistics[$this->trans('commands.site.statistics.messages.vocabulary')] = $this->getEntitiesCount('taxonomy_vocabulary');
            $statistics[$this->trans('commands.site.statistics.messages.taxonomy-terms')] = $this->getEntitiesCount('taxonomy_term');
        }

        if ($this->moduleHandler->moduleExists('file')) {
            $statistics[$this->trans('commands.site.statistics.messages.files')] = $this->getEntitiesCount('file');
        }

        if ($this->moduleHandler->moduleExists('user')) {
            $statistics[$this->trans('commands.site.statistics.messages.users')] = $this->getEntitiesCount('user');
        }

        if ($this->moduleHandler->moduleExists('views')) {
            $statistics[$this->trans('commands.site.statistics.messages.views')] = $this->getEntitiesCount('view', [
                'name' => 'tag',
                'value' => 'default',
                'condition' => '<>',
            ]);
        }

        $statistics[$this->trans('commands.site.statistics.messages.modules-enabled')] = $this->getModuleCount(true);
        $statistics[$this->trans('commands.site.statistics.messages.modules-disabled')] = $this->getModuleCount(false);
        $statistics[$this->trans('commands.site.statistics.messages.themes-enabled')] = $this->getThemeCount(true);
        $statistics[$this->trans('commands.site.statistics.messages.themes-disabled')] = $this->getThemeCount(false);

        $this->statisticsList($statistics);
    }

    private function getEntitiesCount($entity_type, $condition = [])
    {
        $entityQuery = $this->entityQuery->get($entity_type)->count();
        if (!empty($condition)) {
            $entityQuery->condition($condition['name'], $condition['value'], $condition['condition']);
        }

        return $entityQuery->execute();
    }

    /**
     * @param bool|TRUE $status
     * @return int
     */
    private function getModuleCount($status = true)
    {
        $this->extensionManager->discoverModules();
        return $this->getExtensionCount($status);
    }

    /**
     * @param bool|TRUE $status
     * @return int
     */
    private function getThemeCount($status = true)
    {
        $this->extensionManager->discoverThemes();
        return $this->getExtensionCount($status);
    }

    private function getExtensionCount($status = true)
    {
        $this->extensionManager->showCore()->showNoCore();
        if ($status) {
            $this->extensionManager->showInstalled();
        } else {
            $this->extensionManager->showUninstalled();
        }

        return count($this->extensionManager->getList());
    }

    /**
     * @param mixed $statistics
     */
    private function statisticsList($statistics)
    {
        $tableHeader = [
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

        $this->getIo()->table($tableHeader, $tableRows);
    }
}
