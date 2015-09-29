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

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $dialog->askConfirmation(
                $output,
                $dialog->getQuestion($this->trans('commands.config.export.view.questions.optional-config'), 'yes', '?'),
                true
            );
        }
        $input->setOption('optional-config', $optionalConfig);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getEntityManager();
        $this->configStorage = $this->getConfigStorage();

        $module = $input->getOption('module');
        $viewId = $input->getArgument('view-id');
        $optionalConfig = $input->getOption('optional-config');

        $viewTypeDefinition = $this->entityManager->getDefinition('view');
        $viewTypeName = $viewTypeDefinition->getConfigPrefix() . '.' . $viewId;

        $viewNameConfig = $this->getConfiguration($viewTypeName);

        $this->configExport[$viewTypeName] = array('data' => $viewNameConfig, 'optional' => $optionalConfig);

        $this->exportConfig($module, $output);
    }

    protected function exportConfig($module, OutputInterface $output)
    {
        $dumper = new Dumper();

        $output->writeln(
            sprintf(
                '[+] <info>%s</info>',
                $this->trans('commands.views.export.messages.view_exported')
            )
        );

        foreach ($this->configExport as $fileName => $config) {
            $yamlConfig = $dumper->dump($config['data'], 10);

            if ($config['optional']) {
                $configDirectory = $this->getSite()->getModuleConfigOptionalDirectory($module, false);
            } else {
                $configDirectory = $this->getSite()->getModuleConfigInstallDirectory($module, false);
            }

            $configFile = sprintf(
                '%s/%s.yml',
                $configDirectory,
                $fileName
            );

            $output->writeln(
                sprintf(
                    '- <info>%s</info>',
                    $configFile
                )
            );

            $configDirectory = sprintf(
                '%s/%s',
                $this->getSite()->getSitePath(),
                $configDirectory
            );

            if (!file_exists($configDirectory)) {
                mkdir($configDirectory);
            }

            file_put_contents(
                sprintf(
                    '%s/%s',
                    $this->getSite()->getSitePath(),
                    $configFile
                ),
                $yamlConfig
            );
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
