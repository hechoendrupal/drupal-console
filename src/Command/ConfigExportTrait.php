<?php

/**
 * @file
 * Contains Drupal\Console\Command\ConfigExportTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

/**
 * Class ConfigExportTrait
 * @package Drupal\Console\Command
 */
trait ConfigExportTrait
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
                $this->getSite()->getSitePath(),
                $configDirectory
            );

            // Create directory if doesn't exist
            if (!file_exists($configDirectory)) {
                mkdir($configDirectory, 0755, true);
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

    protected function resolveDependencies($dependencies, $optional = FALSE)
    {
        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency, $this->configExport)) {
                $this->configExport[$dependency] = array('data' => $this->getConfiguration($dependency), 'optional' => $optional);
                if (isset($this->configExport[$dependency]['dependencies']['config'])) {
                    $this->resolveDependencies($this->configExport[$dependency]['dependencies']['config']);
                }
            }
        }
    }
}
