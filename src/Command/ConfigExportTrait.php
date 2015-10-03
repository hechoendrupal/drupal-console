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
        // Unset uuid, maybe is not necessary to export
        $config = $this->configStorage->read($configName);

        if (!$uuid) {
            unset($config['uuid']);
        }

        return $config;
    }

    /**
     * @param $module
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
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
}
