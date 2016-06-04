<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ExportTrait.
 */

namespace Drupal\Console\Command\Shared;

use Symfony\Component\Yaml\Dumper;
use \Symfony\Component\Yaml\Yaml;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ConfigExportTrait
 * @package Drupal\Console\Command
 */
trait ExportTrait
{
    /**
     * @param $configName
     * @param bool|false $uuid
     * @return mixed
     */
    protected function getConfiguration($configName, $uuid = false)
    {
        $config = $this->configStorage->read($configName);

        // Exclude uuid base in parameter, useful to share configurations.
        if (!$uuid) {
            unset($config['uuid']);
        }

        return $config;
    }

    /**
     * @param string      $module
     * @param DrupalStyle $io
     */
    protected function exportConfig($module, DrupalStyle $io, $message)
    {
        $dumper = new Dumper();

        $io->info($message);

        foreach ($this->configExport as $fileName => $config) {
            $yamlConfig = $dumper->dump($config['data'], 10);

            if ($config['optional']) {
                $configDirectory = $this->getApplication()->getSite()->getModuleConfigOptionalDirectory($module, false);
            } else {
                $configDirectory = $this->getApplication()->getSite()->getModuleConfigInstallDirectory($module, false);
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
                $this->configExport[$dependency] = array('data' => $this->getConfiguration($dependency), 'optional' => $optional);
                if ($dependencies = $this->fetchDependencies($this->configExport[$dependency], 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function exportModuleDependencies($io, $module, $dependencies)
    {
        $yaml = new Yaml();
        $info_file = file_get_contents($this->getApplication()->getSite()->getModuleInfoFile($module));
        $info_yaml = $yaml->parse($info_file);

        if (empty($info_yaml['dependencies'])) {
            $info_yaml['dependencies'] = $dependencies;
        } else {
            $info_yaml['dependencies'] = array_unique(array_merge($info_yaml['dependencies'], $dependencies));
        }

        if (file_put_contents($this->getApplication()->getSite()->getModuleInfoFile($module), $yaml->dump($info_yaml))) {
            $io->info(
                '[+] ' .
                sprintf(
                    $this->trans('commands.config.export.view.messages.depencies-included'),
                    $this->getApplication()->getSite()->getModuleInfoFile($module)
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
