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
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Console\Utils\ChainQueue;

class ModeCommand extends Command
{
    use ContainerAwareCommandTrait;

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
     * @param ConfigFactory           $configFactory
     * @param ConfigurationManager $configurationManager
     * @param ChainQueue $chainQueue,
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        /* START
         * [site:mode] option for disabling all caches #2350
         * https://github.com/hechoendrupal/DrupalConsole/issues/2350
         *
         */
        $yaml = new Yaml();
        $settings = $this->get('settings');
        $services_file = $settings->getServicesFile();
        // Copy default.services.yml in sites/default/ into sites/default/services.yml
        //@TODO: handle it if services.yml exists
        if (!$settings->exists($services_file)) {
          $settings->rename(
            $settings->getDefaultServicesFile(),
            $settings->getServicesFile()
          );
        }
        $services = $yaml->parse(file_get_contents($services_file));
        $settings_file = $settings->getSettingsFile("local");
        $cache_bins_strings = $settings->getCacheBinsStrings();

        $settings->set_perms(
          [$settings->getSettingsPath(), $settings_file, $settings->getServicesFile()],
          0777
        );

        // Copy sites/example.settings.local.php to sites/default/settings.local.php
        $settings->copy(
          $settings->getExampleSettingsLocalFile(),
          $settings->getSettingsLocalFile()
        );

        // Uncomment the following lines in sites/default/settings.php
        $settings->write(
              $settings_file,
              file_get_contents($settings_file) . $settings->get_settings_local_str()
          );

        // activate $settings['cache']['bins'] in settings.php
        $settings->write(
              $settings->getSettingsLocalFile(),
              file_get_contents($settings_file) . $cache_bins_strings
          );

        $settings->set_perms(
          [$settings->getSettingsPath()],
          0555
        );

        $settings->set_perms(
          [$settings_file, $settings->getServicesFile()],
          0444
        );

                die(3);
        //@TODO: clear cache
        //@TODO: $io->commentBlock()

        /* END
         * [site:mode] option for disabling all caches #2350
         * https://github.com/hechoendrupal/DrupalConsole/issues/2350
         *
         */



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
            $environment,
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

    protected function overrideServices($environment, $servicesSettings, DrupalStyle $io)
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
                $io->error(
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
            if(is_array($parameters)) {
                foreach ($parameters as $parameter => $value) {
                    print 'parameters: ' . $parameter . "\n";
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
        $configFile = sprintf(
            '%s/.console/site.mode.yml',
            $this->configurationManager->getHomeDirectory()
        );

        if (!file_exists($configFile)) {
            $configFile = sprintf(
                '%s/config/dist/site.mode.yml',
                $this->configurationManager->getApplicationDirectory() . DRUPAL_CONSOLE_CORE
            );
        }

        $siteModeConfiguration = Yaml::parse(file_get_contents($configFile));
        $configKeys = array_keys($siteModeConfiguration);

        $configurationSettings = [];
        foreach ($configKeys as $configKey) {
            $siteModeConfigurationItem = $siteModeConfiguration[$configKey];
            foreach ($siteModeConfigurationItem as $setting => $parameters) {
                if(array_key_exists($env, $parameters)) {
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
