<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\ModeCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class ModeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('site:mode')
            ->setDescription($this->trans('commands.site.mode.description'))
            ->addArgument(
                'environment',
                InputArgument::REQUIRED,
                $this->trans('commands.site.mode.arguments.environment')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $environment = $input->getArgument('environment');

        $loadedConfigurations = [];
        if (in_array($environment, array('dev', 'prod'))) {
            $loadedConfigurations = $this->loadConfigurations($environment);
        } else {
            $io->error($this->trans('commands.site.mode.messages.invalid-env'));
        }

        $configurationOverrideResult = $this->overrideConfigurations(
            $loadedConfigurations['configurations']
        );

        foreach ($configurationOverrideResult as $configName => $result) {
            $io->info(
                $this->trans('commands.site.mode.messages.configuration') . ':',
                false
            );
            $io->comment($configName);

            $tableHeader = [
                $this->trans('commands.site.mode.messages.configuration-key'),
                $this->trans('commands.site.mode.messages.original'),
                $this->trans('commands.site.mode.messages.updated'),
            ];

            $io->table($tableHeader, $result);
        }

        $servicesOverrideResult = $this->overrideServices(
            $loadedConfigurations['services'],
            $io
        );

        if (!empty($servicesOverrideResult)) {
            $io->info(
                $this->trans('commands.site.mode.messages.new-services-settings')
            );

            $tableHeaders = [
                $this->trans('commands.site.mode.messages.service'),
                $this->trans('commands.site.mode.messages.service-parameter'),
                $this->trans('commands.site.mode.messages.service-value'),
            ];

            $io->table($tableHeaders, $servicesOverrideResult);
        }

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function overrideConfigurations($configurations)
    {
        $result = [];
        foreach ($configurations as $configName => $options) {
            $config = $this->getConfigFactory()->getEditable($configName);
            foreach ($options as $key => $value) {
                $original = $config->get($key);
                if (is_bool($original)) {
                    $original = $original? 'true' : 'false';
                }
                $updated = $value;
                if (is_bool($updated)) {
                    $updated = $updated? 'true' : 'false';
                }

                $result[$configName][] = [
                    'configuration' => $key,
                    'original' => $original,
                    'updated' => $updated,
                ];
                $config->set($key, $value);
            }
            $config->save();
        }

        return $result;
    }

    protected function overrideServices($servicesSettings, DrupalStyle $io)
    {
        $directory = sprintf(
            '%s/%s',
            $this->getDrupalHelper()->getRoot(),
            \Drupal::service('site.path')
        );

        $settingsServicesFile = $directory . '/services.yml';
        if (!file_exists($settingsServicesFile)) {
            // Copying default services
            $defaultServicesFile = $this->getDrupalHelper()->getRoot() . '/sites/default/default.services.yml';
            if (!copy($defaultServicesFile, $settingsServicesFile)) {
                $io->error(
                    sprintf(
                        '%s: %s /services.yml',
                        $this->trans('commands.site.mode.messages.error-copying-file'),
                        $directory
                    )
                );

                return [];
            }
        }

        $yaml = new Yaml();
        $services = $yaml->parse(file_get_contents($settingsServicesFile));

        $result = [];
        foreach ($servicesSettings as $service => $parameters) {
            foreach ($parameters as $parameter => $value) {
                $services['parameters'][$service][$parameter] = $value;
                // Set values for output
                $result[$parameter]['service'] = $service;
                $result[$parameter]['parameter'] = $parameter;
                if (is_bool($value)) {
                    $value = $value? 'true' : 'false';
                }
                $result[$parameter]['value'] = $value;
            }
        }

        if (file_put_contents($settingsServicesFile, $yaml->dump($services))) {
            $io->commentBlock(
                sprintf(
                    $this->trans('commands.site.mode.messages.services-file-overwritten'),
                    $settingsServicesFile
                )
            );
        } else {
            $io->error(
                sprintf(
                    '%s : %s/services.yml',
                    $this->trans('commands.site.mode.messages.error-writing-file'),
                    $directory
                )
            );

            return [];
        }

        sort($result);
        return $result;
    }

    protected function loadConfigurations($env)
    {
        $config = $this->getApplication()->getConfig();
        $configFile = sprintf(
            '%s/.console/site.mode.yml',
            $config->getUserHomeDir()
        );

        if (!file_exists($configFile)) {
            $configFile = sprintf(
                '%s/config/dist/site.mode.yml',
                $this->getApplication()->getDirectoryRoot()
            );
        }

        $siteModeConfiguration = $config->getFileContents($configFile);
        $configKeys = array_keys($siteModeConfiguration);

        $configurationSettings = [];
        foreach ($configKeys as $configKey) {
            $siteModeConfigurationItem = $siteModeConfiguration[$configKey];
            foreach ($siteModeConfigurationItem as $setting => $parameters) {
                foreach ($parameters as $parameter => $value) {
                    $configurationSettings[$configKey][$setting][$parameter] = $value[$env];
                }
            }
        }

        return $configurationSettings;
    }
}
