<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ExportTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Exception\InvalidOptionException;

/**
 * Class ConfigExportTrait
 *
 * @package Drupal\Console\Command
 */
trait ExportTrait
{
    /**
     * @param $configName
     * @param bool|false $uuid
     * @return mixed
     */
    protected function getConfiguration($configName, $uuid = false, $hash = false, $collection = '')
    {
        $config = $this->configStorage->createCollection($collection)->read($configName);
        // Exclude uuid base in parameter, useful to share configurations.
        if ($uuid) {
            unset($config['uuid']);
        }

        // Exclude default_config_hash inside _core is site-specific.
        if ($hash) {
            unset($config['_core']['default_config_hash']);

            // Remove empty _core to match core's output.
            if (empty($config['_core'])) {
                unset($config['_core']);
            }
        }

        return $config;
    }

    /**
     * @param string $directory
     * @param string $message
     */
    protected function exportConfig($directory, $message)
    {
        $directory = realpath($directory);
        $this->getIo()->info($message);

        foreach ($this->configExport as $fileName => $config) {
            $yamlConfig = Yaml::encode($config['data']);

            $configFile = sprintf(
                '%s/%s.yml',
                $directory,
                $fileName
            );

            $this->getIo()->writeln("- $configFile");

            // Create directory if doesn't exist
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents(
                $configFile,
                $yamlConfig
            );
        }
    }

    /**
     * @param string $moduleName
     * @param string $message
     */
    protected function exportConfigToModule($moduleName, $message)
    {
        $this->getIo()->info($message);

        $module = $this->extensionManager->getModule($moduleName);

        if (empty($module)) {
            throw new InvalidOptionException(sprintf('The module %s does not exist.', $moduleName));
        }

        foreach ($this->configExport as $fileName => $config) {
            $yamlConfig = Yaml::encode($config['data']);

            if ($config['optional']) {
                $configDirectory = $module->getConfigOptionalDirectory(false);
            } else {
                $configDirectory = $module->getConfigInstallDirectory(false);
            }

            $configFile = sprintf(
                '%s/%s.yml',
                $configDirectory,
                $fileName
            );

            $this->getIo()->writeln("- $configFile");

            // Create directory if doesn't exist
            if (!file_exists($configDirectory)) {
                mkdir($configDirectory, 0755, true);
            }

            file_put_contents(
                $configFile,
                $yamlConfig
            );
        }

        $this->configExport = [];
    }

    protected function fetchDependencies($config, $type = 'config')
    {
        if (isset($config['dependencies'][$type])) {
            return $config['dependencies'][$type];
        }

        return null;
    }

    protected function resolveDependencies($dependencies, $optional = false, $uuid = false, $hash = false)
    {
        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency, $this->configExport)) {
                $this->configExport[$dependency] = [
                    'data' => $this->getConfiguration($dependency, $uuid, $hash),
                    'optional' => $optional
                ];

                if ($dependencies = $this->fetchDependencies($this->configExport[$dependency], 'config')) {
                    $this->resolveDependencies($dependencies, $optional, $uuid, $hash);
                }
            }
        }
    }

    protected function exportModuleDependencies($module, $dependencies)
    {
        $module = $this->extensionManager->getModule($module);
        $info_yaml = Yaml::decode(file_get_contents($module->getPathname(), true));

        if (empty($info_yaml['dependencies'])) {
            $info_yaml['dependencies'] = $dependencies;
        } else {
            $info_yaml['dependencies'] = array_unique(array_merge($info_yaml['dependencies'], $dependencies));
        }

        if (file_put_contents($module->getPathname(), Yaml::encode($info_yaml))) {
            $this->getIo()->info(
                '[+] ' .
                sprintf(
                    $this->trans('commands.config.export.view.messages.depencies-included'),
                    $module->getPathname()
                )
            );

            foreach ($dependencies as $dependency) {
                $this->getIo()->info(
                    "   [-] $dependency"
                );
            }
        } else {
            $this->getIo()->error("{$this->trans('commands.site.mode.messages.error-writing-file')}: {$this->getApplication()->getSite()->getModuleInfoFile($module)}");

            return [];
        }
    }

    protected function getFields(
        $bundle,
        $optional = false,
        $removeUuid = false,
        $removeHash = false
    ) {

        $fields_definition = $this->entityTypeManager->getDefinition('field_config');

        $fields_storage = $this->entityTypeManager->getStorage('field_config');
        foreach ($fields_storage->loadMultiple() as $field) {
            $field_name = "{$fields_definition->getConfigPrefix()}.{$field->id()}";
            $field_name_config = $this->getConfiguration($field_name, $removeUuid,
                $removeHash);
            // Only select fields related with content type
            if ($field_name_config['bundle'] == $bundle) {
                $this->configExport[$field_name] = [
                    'data' => $field_name_config,
                    'optional' => $optional,
                ];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($field_name_config,
                    'config')) {
                    $this->resolveDependencies($dependencies, $optional, $removeUuid, $removeHash);
                }
            }
        }
    }

    protected function getBasefieldOverrideFields(
        $bundle = null,
        $optional = false,
        $removeUuid = false,
        $removeHash = false,
        $collection = ''
    ) {
        $collection_storage = $this->storage->createCollection($collection);
        foreach ($collection_storage->listAll() as $name) {
            if(strpos($name, "core.base_field_override.node") !== false) {
                $configData = $collection_storage->read($name);
                if ($removeUuid) {
                    unset($configData['uuid']);
                }
                if ($removeHash) {
                    unset($configData['_core']['default_config_hash']);
                    if (empty($configData['_core'])) {
                        unset($configData['_core']);
                    }
                }

                if ($configData['bundle'] == $bundle) {
                    $this->configExport["$name.yml"] = [
                        'data' => $configData,
                        'optional' => $optional,
                    ];
                    // Include dependencies in export files
                    if ($dependencies = $this->fetchDependencies($configData,
                        'config')) {
                        $this->resolveDependencies($dependencies, $optional, $removeUuid, $removeHash);
                    }
                }
            }
        }
    }

    protected function getFormDisplays(
        $bundle,
        $optional = false,
        $removeUuid = false,
        $removeHash = false
    ) {
        $arr = [];

        $form_display_definition = $this->entityTypeManager->getDefinition('entity_form_display');
        $form_display_storage = $this->entityTypeManager->getStorage('entity_form_display');
        foreach ($form_display_storage->loadMultiple() as $form_display) {
            $form_display_name = "{$form_display_definition->getConfigPrefix()}.{$form_display->id()}";
            $form_display_name_config = $this->getConfiguration($form_display_name,
                $removeUuid, $removeHash);

            $arr[$form_display_name_config['bundle']] = $form_display_name_config['bundle'];
            // Only select fields related with content type
            if ($form_display_name_config['bundle'] == $bundle) {
                $this->configExport[$form_display_name] = [
                    'data' => $form_display_name_config,
                    'optional' => $optional,
                ];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($form_display_name_config,
                    'config')) {
                    $this->resolveDependencies($dependencies, $optional, $removeUuid, $removeHash);
                }
            }
        }
    }

    protected function getViewDisplays(
        $bundle,
        $optional = false,
        $removeUuid = false,
        $removeHash = false
    ) {
        $view_display_definition = $this->entityTypeManager->getDefinition('entity_view_display');
        $view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
        foreach ($view_display_storage->loadMultiple() as $view_display) {
            $view_display_name = "{$view_display_definition->getConfigPrefix()}.{$view_display->id()}";
            $view_display_name_config = $this->getConfiguration($view_display_name,
                $removeUuid, $removeHash);
            // Only select fields related with content type
            if ($view_display_name_config['bundle'] == $bundle) {
                $this->configExport[$view_display_name] = [
                    'data' => $view_display_name_config,
                    'optional' => $optional,
                ];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($view_display_name_config,
                    'config')) {
                    $this->resolveDependencies($dependencies, $optional, $removeUuid, $removeHash);
                }
            }
        }
    }
}
