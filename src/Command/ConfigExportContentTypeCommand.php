<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigExportCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Yaml\Dumper;

class ConfigExportContentTypeCommand extends ContainerAwareCommand
{
    use ModuleTrait;

    protected $entity_manager;
    protected $configStorage;
    protected $config_export;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:export:content:type')
            ->setDescription($this->trans('commands.config.export.content.type.description'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument(
                'content_type',
                InputArgument::REQUIRED,
                $this->trans('commands.config.export.content.type.arguments.content_type')
            );

        $this->config_export = array();
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
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --content type argument
        $content_type = $input->getArgument('content_type');
        if (!$content_type) {
            $entity_manager = $this->getEntityManager();
            $bundles_entities = $entity_manager->getStorage('node_type')->loadMultiple();
            $bundles = array();
            foreach ($bundles_entities as $entity) {
                $bundles[$entity->id()] = $entity->label();
            }

            $content_type = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.config.export.content.type.questions.content_type'), ''),
                function ($bundle) use ($bundles) {
                    if (!in_array($bundle, array_values($bundles))) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Content type "%s" is invalid.',
                                $bundle
                            )
                        );
                    }

                    return array_search($bundle, $bundles);
                },
                false,
                '',
                $bundles
            );
        }
        $input->setArgument('content_type', $content_type);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entity_manager = $this->getEntityManager();
        $this->configStorage = $this->getConfigStorage();

        $module = $input->getOption('module');
        $content_type = $input->getArgument('content_type');

        $content_type_definition = $this->entity_manager->getDefinition('node_type');
        $content_type_name = $content_type_definition->getConfigPrefix() . '.' . $content_type;

        $content_type_name_config = $this->getConfiguration($content_type_name);

        $this->config_export[$content_type_name] = $content_type_name_config;

        $this->getFields($content_type);

        $this->getFormDisplays($content_type);

        $this->getViewDisplays($content_type);

        $this->exportConfig($module, $output);
    }

    protected function getFields($content_type)
    {
        $fields_definition = $this->entity_manager->getDefinition('field_config');

        $fields_storage = $this->entity_manager->getStorage('field_config');
        foreach ($fields_storage->loadMultiple() as $field) {
            $field_name = $fields_definition->getConfigPrefix() . '.' . $field->id();
            $field_name_config = $this->getConfiguration($field_name);
            // Only select fields related with content type
            if ($field_name_config['bundle'] == $content_type) {
                $this->config_export[$field_name] = $field_name_config;
                // Include dependencies in export files
                $this->resolveDependencies($field_name_config['dependencies']['config']);
            }
        }
    }

    protected function getFormDisplays($content_type)
    {
        $form_display_definition = $this->entity_manager->getDefinition('entity_form_display');
        $form_display_storage = $this->entity_manager->getStorage('entity_form_display');
        foreach ($form_display_storage->loadMultiple() as $form_display) {
            $form_display_name = $form_display_definition->getConfigPrefix() . '.' . $form_display->id();
            $form_display_name_config = $this->getConfiguration($form_display_name);
            // Only select fields related with content type
            if ($form_display_name_config['bundle'] == $content_type) {
                $this->config_export[$form_display_name] = $form_display_name_config;
                // Include dependencies in export files
                $this->resolveDependencies($form_display_name_config['dependencies']['config']);
            }
        }
    }

    protected function getViewDisplays($content_type)
    {
        $view_display_definition = $this->entity_manager->getDefinition('entity_view_display');
        $view_display_storage = $this->entity_manager->getStorage('entity_view_display');
        foreach ($view_display_storage->loadMultiple() as $view_display) {
            $view_display_name = $view_display_definition->getConfigPrefix() . '.' . $view_display->id();
            $view_display_name_config = $this->getConfiguration($view_display_name);
            // Only select fields related with content type
            if ($view_display_name_config['bundle'] == $content_type) {
                $this->config_export[$view_display_name] = $view_display_name_config;
                // Include dependencies in export files
                $this->resolveDependencies($view_display_name_config['dependencies']['config']);
            }
        }
    }

    protected function resolveDependencies($dependencies)
    {
        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency, $this->config_export)) {
                $this->config_export[$dependency] = $this->getConfiguration($dependency);
                if (isset($this->config_export[$dependency]['dependencies']['config'])) {
                    $this->resolveDependencies($this->config_export[$dependency]['dependencies']['config']);
                }
            }
        }
    }
    protected function getConfiguration($config_name)
    {
        // Unset uuid, maybe is not necessary to export
        $config = $this->configStorage->read($config_name);
        unset($config['uuid']);
        return $config;
    }

    protected function exportConfig($module, OutputInterface $output)
    {
        $dumper = new Dumper();

        $module_path =  $this->getSite()->getModulePath($module);
        if (!file_exists($module_path .'/config')) {
            mkdir($module_path .'/config', 0755, true);
        }

        if (!file_exists($module_path .'/config/install')) {
            mkdir($module_path .'/config/install', 0755, true);
        }

        $output->writeln(
            '[+] <info>' .
            $this->trans('commands.config.export.content.type.messages.configuration_exported') .
            '</info>'
        );

        foreach ($this->config_export as $file_name => $config) {
            $yaml_config = $dumper->dump($config, 10);
            $output->writeln(
                '- <info>' .
                str_replace(DRUPAL_ROOT, '', $module_path)  . '/config/install/' . $file_name . '.yml' .
                '</info>'
            );
            file_put_contents($module_path . '/config/install/' . $file_name . '.yml', $yaml_config);
        }
    }
}
