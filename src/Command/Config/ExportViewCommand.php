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
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Style\DrupalStyle;

class ExportViewCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ExportTrait;

    protected $entityManager;
    protected $configStorage;
    protected $configExport;

    protected function configure()
    {
        $this
            ->setName('config:export:view')
            ->setDescription($this->trans('commands.config.export.view.description'))
            ->addOption(
                'module', '',
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
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.view.options.optional-config')
            )
            ->addOption(
                'include-module-dependencies',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.view.options.include-module-dependencies')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // view-id argument
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $entityManager = $this->getEntityManager();
            $views = $entityManager->getStorage('view')->loadMultiple();

            $viewList = [];
            foreach ($views as $view) {
                $viewList[$view->get('id')] = $view->get('label');
            }

            $viewId = $output->choiceNoList(
                $this->trans('commands.views.export.questions.view'),
                $viewList
            );
            $input->setArgument('view-id', $viewId);
        }

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $output->confirm(
                $this->trans('commands.config.export.view.questions.optional-config'),
                true
            );
            $input->setOption('optional-config', $optionalConfig);
        }

        $includeModuleDependencies = $input->getOption('include-module-dependencies');
        if (!$includeModuleDependencies) {
            $includeModuleDependencies = $output->confirm(
                $this->trans('commands.config.export.view.questions.include-module-dependencies'),
                true
            );
            $input->setOption('include-module-dependencies', $includeModuleDependencies);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getEntityManager();
        $this->configStorage = $this->getConfigStorage();

        $module = $input->getOption('module');
        $viewId = $input->getArgument('view-id');
        $optionalConfig = $input->getOption('optional-config');
        $includeModuleDependencies = $input->getOption('include-module-dependencies');

        $viewTypeDefinition = $this->entityManager->getDefinition('view');
        $viewTypeName = $viewTypeDefinition->getConfigPrefix() . '.' . $viewId;

        $viewNameConfig = $this->getConfiguration($viewTypeName);

        $this->configExport[$viewTypeName] = array('data' => $viewNameConfig, 'optional' => $optionalConfig);

        // Include config dependencies in export files
        if ($dependencies = $this->fetchDependencies($viewNameConfig, 'config')) {
            $this->resolveDependencies($dependencies, $optionalConfig);
        }

        // Include module dependencies in export files if export is not optional
        if ($includeModuleDependencies) {
            if ($dependencies = $this->fetchDependencies($viewNameConfig, 'module')) {
                $this->exportModuleDependencies($output, $module, $dependencies);
            }
        }

        $this->exportConfig($module, $output, $this->trans('commands.views.export.messages.view_exported'));
    }
}
