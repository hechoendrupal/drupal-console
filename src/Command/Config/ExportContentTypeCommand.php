<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportContentTypeCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Command\ModuleTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class ExportContentTypeCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ExportTrait;

    protected $entity_manager;
    protected $configStorage;
    protected $configExport;

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
                'content-type',
                InputArgument::REQUIRED,
                $this->trans('commands.config.export.content.type.arguments.content-type')
            )->addOption(
                'optional-config',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.content.type.options.optional-config')
            );

        $this->configExport = array();
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
        }
        $input->setOption('module', $module);

        // --content-type argument
        $contentType = $input->getArgument('content-type');
        if (!$contentType) {
            $entity_manager = $this->getEntityManager();
            $bundles_entities = $entity_manager->getStorage('node_type')->loadMultiple();
            $bundles = array();
            foreach ($bundles_entities as $entity) {
                $bundles[] = $entity->label();
            }

            $contentType = $output->choice(
                $this->trans('commands.config.export.content.type.questions.content-type'),
                $bundles
            );
        }
        $input->setArgument('content-type', $contentType);

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $output->confirm(
                $this->trans('commands.config.export.content.type.questions.optional-config'),
                true
            );
        }
        $input->setOption('optional-config', $optionalConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entity_manager = $this->getEntityManager();
        $this->configStorage = $this->getConfigStorage();

        $module = $input->getOption('module');
        $contentType = $input->getArgument('content-type');
        $optionalConfig = $input->getOption('optional-config');

        $contentTypeDefinition = $this->entity_manager->getDefinition('node_type');
        $contentTypeName = $contentTypeDefinition->getConfigPrefix() . '.' . $contentType;

        $contentTypeNameConfig = $this->getConfiguration($contentTypeName);

        $this->configExport[$contentTypeName] = array('data' => $contentTypeNameConfig, 'optional' => $optionalConfig);

        $this->getFields($contentType, $optionalConfig);

        $this->getFormDisplays($contentType, $optionalConfig);

        $this->getViewDisplays($contentType, $optionalConfig);

        $this->exportConfig($module, $output, $this->trans('commands.config.export.content.type.messages.content_type_exported'));
    }

    protected function getFields($contentType, $optional = false)
    {
        $fields_definition = $this->entity_manager->getDefinition('field_config');

        $fields_storage = $this->entity_manager->getStorage('field_config');
        foreach ($fields_storage->loadMultiple() as $field) {
            $field_name = $fields_definition->getConfigPrefix() . '.' . $field->id();
            $field_name_config = $this->getConfiguration($field_name);
            // Only select fields related with content type
            if ($field_name_config['bundle'] == $contentType) {
                $this->configExport[$field_name] = array('data' => $field_name_config, 'optional' => $optional);
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($field_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function getFormDisplays($contentType, $optional = false)
    {
        $form_display_definition = $this->entity_manager->getDefinition('entity_form_display');
        $form_display_storage = $this->entity_manager->getStorage('entity_form_display');
        foreach ($form_display_storage->loadMultiple() as $form_display) {
            $form_display_name = $form_display_definition->getConfigPrefix() . '.' . $form_display->id();
            $form_display_name_config = $this->getConfiguration($form_display_name);
            // Only select fields related with content type
            if ($form_display_name_config['bundle'] == $contentType) {
                $this->configExport[$form_display_name] = array('data' => $form_display_name_config, 'optional' => $optional);
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($form_display_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function getViewDisplays($contentType, $optional = false)
    {
        $view_display_definition = $this->entity_manager->getDefinition('entity_view_display');
        $view_display_storage = $this->entity_manager->getStorage('entity_view_display');
        foreach ($view_display_storage->loadMultiple() as $view_display) {
            $view_display_name = $view_display_definition->getConfigPrefix() . '.' . $view_display->id();
            $view_display_name_config = $this->getConfiguration($view_display_name);
            // Only select fields related with content type
            if ($view_display_name_config['bundle'] == $contentType) {
                $this->configExport[$view_display_name] = array('data' => $view_display_name_config, 'optional' => $optional);
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($view_display_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }
}
