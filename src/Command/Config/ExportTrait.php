<?php

/**
 * @file
 * Contains Drupal\Console\Command\Config\ExportTrait.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

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
     * @param $module
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function exportConfig($module, OutputInterface $output, $message)
    {
        $dumper = new Dumper();

        $output->writeln(
            sprintf(
                '[+] <info>%s</info>',
                $message
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
                $this->getKernelHelper()->getSitePath(),
                $configDirectory
            );

            // Create directory if doesn't exist
            if (!file_exists($configDirectory)) {
                mkdir($configDirectory, 0755, true);
            }

            file_put_contents(
                sprintf(
                    '%s/%s',
                    $this->getKernelHelper()->getSitePath(),
                    $configFile
                ),
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

    protected function exportModuleDependencies($output, $module, $dependencies)
    {
        $yaml = new \Symfony\Component\Yaml\Yaml();
        $info_file = file_get_contents($this->getSite()->getModuleInfoFile($module));
        $info_yaml = $yaml->parse($info_file);

        if (empty($info_yaml['dependencies'])) {
            $info_yaml['dependencies'] = $dependencies;
        } else {
            $info_yaml['dependencies'] = array_unique(array_merge($info_yaml['dependencies'], $dependencies));
        }

        if (file_put_contents($this->getSite()->getModuleInfoFile($module), $yaml->dump($info_yaml))) {
            $output->writeln(
                '<info>[+] ' .
                sprintf(
                    $this->trans('commands.config.export.view.messages.depencies-included'),
                    $this->getSite()->getModuleInfoFile($module)
                ) . '</info>'
            );

            foreach ($dependencies as $dependency) {
                $output->writeln(
                    '<info>    [-] ' . $dependency . '</info>'
                );
            }

            $output->writeln('');
        } else {
            $output->writeln(
                ' <error>'. $this->trans('commands.site.mode.messages.error-writing-file') . ': ' . $this->getSite()->getModuleInfoFile($module) .'</error>'
            );
            return [];
        }
    }
}
