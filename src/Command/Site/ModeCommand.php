<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\ModeCommand.
 */

namespace Drupal\Console\Command\Site;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ModeCommand extends Command
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * DebugCommand constructor.
     *
     * @param ConfigFactory        $configFactory
     * @param ConfigurationManager $configurationManager
     * @param $appRoot,
     * @param ChainQueue           $chainQueue,
     */
    public function __construct(
        ConfigFactory $configFactory,
        ConfigurationManager $configurationManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->configFactory = $configFactory;
        $this->configurationManager = $configurationManager;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('site:mode')
            ->setDescription($this->trans('commands.site.mode.description'))
            ->addArgument(
                'environment',
                InputArgument::REQUIRED,
                $this->trans('commands.site.mode.arguments.environment')
            )
            ->setAliases(['smo']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getArgument('environment');

        if (!in_array($environment, ['dev', 'prod'])) {
            $this->getIo()->error($this->trans('commands.site.mode.messages.invalid-env'));
            return 1;
        }

        $loadedConfigurations = $this->loadConfigurations($environment);

        $configurationOverrideResult = $this->overrideConfigurations(
            $loadedConfigurations['configurations']
        );

        foreach ($configurationOverrideResult as $configName => $result) {
            $this->getIo()->info(
                $this->trans('commands.site.mode.messages.configuration') . ':',
                false
            );
            $this->getIo()->comment($configName);

            $tableHeader = [
                $this->trans('commands.site.mode.messages.configuration-key'),
                $this->trans('commands.site.mode.messages.original'),
                $this->trans('commands.site.mode.messages.updated'),
            ];

            $this->getIo()->table($tableHeader, $result);
        }

        $servicesOverrideResult = $this->processServicesFile(
            $environment,
            $loadedConfigurations['services']
        );

        if (!empty($servicesOverrideResult)) {
            $this->getIo()->info(
                $this->trans('commands.site.mode.messages.new-services-settings')
            );

            $tableHeaders = [
                $this->trans('commands.site.mode.messages.service'),
                $this->trans('commands.site.mode.messages.service-parameter'),
                $this->trans('commands.site.mode.messages.service-value'),
            ];

            $this->getIo()->table($tableHeaders, $servicesOverrideResult);
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function overrideConfigurations($configurations)
    {
        $result = [];
        foreach ($configurations as $configName => $options) {
            $config = $this->configFactory->getEditable($configName);
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

    protected function processServicesFile($environment, $servicesSettings)
    {
        $directory = sprintf(
            '%s/%s',
            $this->appRoot,
            \Drupal::service('site.path')
        );

        $settingsServicesFile = $directory . '/services.yml';

        if (!file_exists($settingsServicesFile)) {
            // Copying default services
            $defaultServicesFile = $this->appRoot . '/sites/default/default.services.yml';
            if (!copy($defaultServicesFile, $settingsServicesFile)) {
                $this->getIo()->error(
                    sprintf(
                        '%s: %s/services.yml',
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
            if (is_array($parameters)) {
                foreach ($parameters as $parameter => $value) {
                    $services['parameters'][$service][$parameter] = $value;
                    // Set values for output
                    $result[$parameter]['service'] = $service;
                    $result[$parameter]['parameter'] = $parameter;
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }
                    $result[$parameter]['value'] = $value;
                }
            } else {
                $services['parameters'][$service] = $parameters;
                // Set values for output
                $result[$service]['service'] = $service;
                $result[$service]['parameter'] = '';
                if (is_bool($parameters)) {
                    $value = $parameters ? 'true' : 'false';
                }
                $result[$service]['value'] = $value;
            }
        }

        if (file_put_contents($settingsServicesFile, $yaml->dump($services))) {
            $this->getIo()->commentBlock(
                sprintf(
                    $this->trans('commands.site.mode.messages.services-file-overwritten'),
                    $settingsServicesFile
                )
            );
        } else {
            $this->getIo()->error(
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
        $configFile = $this->configurationManager
            ->getVendorCoreDirectory() . 'site.mode.yml';

        $siteModeConfiguration = Yaml::parse(file_get_contents($configFile));
        $configKeys = array_keys($siteModeConfiguration);

        $configurationSettings = [];
        foreach ($configKeys as $configKey) {
            $siteModeConfigurationItem = $siteModeConfiguration[$configKey];
            foreach ($siteModeConfigurationItem as $setting => $parameters) {
                if (array_key_exists($env, $parameters)) {
                    $configurationSettings[$configKey][$setting] = $parameters[$env];
                } else {
                    foreach ($parameters as $parameter => $value) {
                        $configurationSettings[$configKey][$setting][$parameter] = $value[$env];
                    }
                }
            }
        }

        return $configurationSettings;
    }
}
