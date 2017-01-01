<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ExportTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Core\Style\DrupalStyle;

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
    protected function getConfiguration($configName, $uuid = false, $hash = false)
    {
        $config = $this->configStorage->read($configName);

        // Exclude uuid base in parameter, useful to share configurations.
        if ($uuid) {
            unset($config['uuid']);
        }
        
        // Exclude default_config_hash inside _core is site-specific.
        if ($hash) {
            unset($config['_core']['default_config_hash']);
        }
        
        return $config;
    }

    /**
     * @param string      $directory
     * @param DrupalStyle $io
     */
    protected function exportConfig($directory, DrupalStyle $io, $message)
    {
        $io->info($message);

        foreach ($this->configExport as $fileName => $config) {
            $yamlConfig = Yaml::encode($config['data']);

            $configFile = sprintf(
                '%s/%s.yml',
                $directory,
                $fileName
            );

            $io->info('- ' . $configFile);

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
     * @param string      $module
     * @param DrupalStyle $io
     */
    protected function exportConfigToModule($module, DrupalStyle $io, $message)
    {
        $io->info($message);

        $module = $this->extensionManager->getModule($module);

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

            $io->info('- ' . $configFile);

            // Create directory if doesn't exist
            if (!file_exists($configDirectory)) {
                mkdir($configDirectory, 0755, true);
            }

            file_put_contents(
                $configFile,
                $yamlConfig
            );
        }
    }

    protected function fetchDependencies($config, $type = 'config')
    {
        if (isset($config['dependencies'][$type])) {
            return $config['dependencies'][$type];
        }

        return null;
    }

    protected function resolveDependencies($dependencies, $optional = false)
    {
        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency, $this->configExport)) {
                $this->configExport[$dependency] = ['data' => $this->getConfiguration($dependency), 'optional' => $optional];
                if ($dependencies = $this->fetchDependencies($this->configExport[$dependency], 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function exportModuleDependencies($io, $module, $dependencies)
    {
        $module = $this->extensionManager->getModule($module);
        $info_yaml = $module->info;

        if (empty($info_yaml['dependencies'])) {
            $info_yaml['dependencies'] = $dependencies;
        } else {
            $info_yaml['dependencies'] = array_unique(array_merge($info_yaml['dependencies'], $dependencies));
        }

        if (file_put_contents($module->getPathname(), Yaml::encode($info_yaml))) {
            $io->info(
                '[+] ' .
                sprintf(
                    $this->trans('commands.config.export.view.messages.depencies-included'),
                    $module->getPathname()
                )
            );

            foreach ($dependencies as $dependency) {
                $io->info(
                    '   [-] ' . $dependency
                );
            }
        } else {
            $io->error($this->trans('commands.site.mode.messages.error-writing-file') . ': ' . $this->getApplication()->getSite()->getModuleInfoFile($module));

            return [];
        }
    }
}
