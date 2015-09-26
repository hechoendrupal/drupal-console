<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ConfigExportViewCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

class ConfigExportViewCommand extends ContainerAwareCommand
{
    use ModuleTrait;

    protected $entityManager;
    protected $configStorage;
    protected $configExport;

    protected function configure()
    {
        $this
            ->setName('config:export:view')
            ->setDescription($this->trans('commands.views.export.description'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-id')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);


        // view-id argument
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $entityManager = $this->getEntityManager();
            $views = $entityManager->getStorage('view')->loadMultiple();

            $viewList = [];
            foreach ($views as $view) {
                $viewList[$view->get('id')] = $view->get('label');
            }

            $viewId = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.views.export.questions.view'), ''),
                function ($view) use ($viewList) {
                    if (!in_array($view, array_values($viewList))) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'View "%s" is invalid.',
                                $view
                            )
                        );
                    }

                    return array_search($view, $viewList);
                },
                false,
                '',
                $viewList
            );
        }
        $input->setArgument('view-id', $viewId);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getEntityManager();
        $this->configStorage = $this->getConfigStorage();

        $module = $input->getOption('module');
        $viewId = $input->getArgument('view-id');

        $viewTypeDefinition = $this->entityManager->getDefinition('view');
        $viewTypeName = $viewTypeDefinition->getConfigPrefix() . '.' . $viewId;

        $viewNameConfig = $this->getConfiguration($viewTypeName);

        $this->configExport[$viewTypeName] = $viewNameConfig;

        $this->exportConfig($module, $output);
    }

    protected function exportConfig($module, OutputInterface $output)
    {
        $dumper = new Dumper();

        $modulePath = $this->getSite()->getModulePath($module);
        $this->getSite()->createModuleConfigInstallDirectory($module);

        $output->writeln(
            sprintf(
                '[+] <info>%s</info>',
                $this->trans('commands.views.export.messages.view_exported')
            )
        );

        foreach ($this->configExport as $file_name => $config) {
            $yamlConfig = $dumper->dump($config, 10);
            $output->writeln(
                sprintf(
                    '- <info>%s</info>',
                    sprintf(
                        '%s/%s.yml',
                        $this->getSite()->getModuleConfigInstallDirectory($module, false),
                        $file_name
                    )
                )
            );
            file_put_contents($modulePath . '/config/install/' . $file_name . '.yml', $yamlConfig);
        }
    }

    protected function getConfiguration($configName)
    {
        // Unset uuid, maybe is not necessary to export
        $config = $this->configStorage->read($configName);
        unset($config['uuid']);
        return $config;
    }
}
