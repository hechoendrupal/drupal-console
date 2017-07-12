<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportViewCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Extension\Manager;

class ExportViewCommand extends Command
{
    use CommandTrait;
    use ModuleTrait;
    use ExportTrait;

    protected $configExport;


    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * ExportViewCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param CachedStorage              $configStorage
     * @param Manager                    $extensionManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CachedStorage $configStorage,
        Manager $extensionManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->configStorage = $configStorage;
        $this->extensionManager = $extensionManager;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('config:export:view')
            ->setDescription($this->trans('commands.config.export.view.description'))
            ->addOption(
                'module', null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.export.view.arguments.view-id')
            )
            ->addOption(
                'optional-config',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.view.options.optional-config')
            )
            ->addOption(
                'include-module-dependencies',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.view.options.include-module-dependencies')
            )
            ->setAliases(['cev']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // view-id argument
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $views = $this->entityTypeManager->getStorage('view')->loadMultiple();

            $viewList = [];
            foreach ($views as $view) {
                $viewList[$view->get('id')] = $view->get('label');
            }

            $viewId = $io->choiceNoList(
                $this->trans('commands.config.export.view.questions.view'),
                $viewList
            );
            $input->setArgument('view-id', $viewId);
        }

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $io->confirm(
                $this->trans('commands.config.export.view.questions.optional-config'),
                true
            );
            $input->setOption('optional-config', $optionalConfig);
        }

        $includeModuleDependencies = $input->getOption('include-module-dependencies');
        if (!$includeModuleDependencies) {
            $includeModuleDependencies = $io->confirm(
                $this->trans('commands.config.export.view.questions.include-module-dependencies'),
                true
            );
            $input->setOption('include-module-dependencies', $includeModuleDependencies);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getOption('module');
        $viewId = $input->getArgument('view-id');
        $optionalConfig = $input->getOption('optional-config');
        $includeModuleDependencies = $input->getOption('include-module-dependencies');

        $viewTypeDefinition = $this->entityTypeManager->getDefinition('view');
        $viewTypeName = $viewTypeDefinition->getConfigPrefix() . '.' . $viewId;

        $viewNameConfig = $this->getConfiguration($viewTypeName);

        $this->configExport[$viewTypeName] = ['data' => $viewNameConfig, 'optional' => $optionalConfig];

        // Include config dependencies in export files
        if ($dependencies = $this->fetchDependencies($viewNameConfig, 'config')) {
            $this->resolveDependencies($dependencies, $optionalConfig);
        }

        // Include module dependencies in export files if export is not optional
        if ($includeModuleDependencies) {
            if ($dependencies = $this->fetchDependencies($viewNameConfig, 'module')) {
                $this->exportModuleDependencies($io, $module, $dependencies);
            }
        }

        $this->exportConfigToModule($module, $io, $this->trans('commands.views.export.messages.view-exported'));
    }
}
