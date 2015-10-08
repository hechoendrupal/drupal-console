<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ConfigExportCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Command\ModuleTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Yaml\Dumper;

class ConfigExportContentTypeCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ConfigExportTrait;

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
                'content_type',
                InputArgument::REQUIRED,
                $this->trans('commands.config.export.content.type.arguments.content_type')
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
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
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

            $optionalConfig = $input->getOption('optional-config');
            if (!$optionalConfig) {
                $optionalConfig = $dialog->askConfirmation(
                    $output,
                    $dialog->getQuestion($this->trans('commands.config.export.content.type.questions.optional-config'), 'yes', '?'),
                    true
                );
            }
            $input->setOption('optional-config', $optionalConfig);
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
        $optionalConfig = $input->getOption('optional-config');

        $content_type_definition = $this->entity_manager->getDefinition('node_type');
        $content_type_name = $content_type_definition->getConfigPrefix() . '.' . $content_type;

        $content_type_name_config = $this->getConfiguration($content_type_name);

        $this->configExport[$content_type_name] = array('data' => $content_type_name_config, 'optional' => $optionalConfig);

        $this->getFields($content_type, $optionalConfig);

        $this->getFormDisplays($content_type, $optionalConfig);

        $this->getViewDisplays($content_type, $optionalConfig);

        $this->exportConfig($module, $output, $this->trans('commands.config.export.content.type.messages.content_type_exported'));
    }

    protected function getFields($content_type, $optional = false)
    {
        $fields_definition = $this->entity_manager->getDefinition('field_config');

        $fields_storage = $this->entity_manager->getStorage('field_config');
        foreach ($fields_storage->loadMultiple() as $field) {
            $field_name = $fields_definition->getConfigPrefix() . '.' . $field->id();
            $field_name_config = $this->getConfiguration($field_name);
            // Only select fields related with content type
            if ($field_name_config['bundle'] == $content_type) {
                $this->configExport[$field_name] = array('data' => $field_name_config, 'optional' => $optional);
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($field_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function getFormDisplays($content_type, $optional = false)
    {
        $form_display_definition = $this->entity_manager->getDefinition('entity_form_display');
        $form_display_storage = $this->entity_manager->getStorage('entity_form_display');
        foreach ($form_display_storage->loadMultiple() as $form_display) {
            $form_display_name = $form_display_definition->getConfigPrefix() . '.' . $form_display->id();
            $form_display_name_config = $this->getConfiguration($form_display_name);
            // Only select fields related with content type
            if ($form_display_name_config['bundle'] == $content_type) {
                $this->configExport[$form_display_name] = array('data' => $form_display_name_config, 'optional' => $optional);
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($form_display_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function getViewDisplays($content_type, $optional = false)
    {
        $view_display_definition = $this->entity_manager->getDefinition('entity_view_display');
        $view_display_storage = $this->entity_manager->getStorage('entity_view_display');
        foreach ($view_display_storage->loadMultiple() as $view_display) {
            $view_display_name = $view_display_definition->getConfigPrefix() . '.' . $view_display->id();
            $view_display_name_config = $this->getConfiguration($view_display_name);
            // Only select fields related with content type
            if ($view_display_name_config['bundle'] == $content_type) {
                $this->configExport[$view_display_name] = array('data' => $view_display_name_config, 'optional' => $optional);
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($view_display_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }
}
