<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\ModeCommand.
 */
namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Novia713\Maginot\Maginot;
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
     *
     * @param ConfigFactory        $configFactory
     * @param ConfigurationManager $configurationManager
     * @param $appRoot
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

        $this->local = null;

        $this->services_file =
            $this->appRoot.'/sites/default/services.yml';

        $this->local_services_file =
            $this->appRoot.'/sites/development.services.yml';

        $this->settings_file =
            $this->appRoot.'/sites/default/settings.php';

        $this->local_settings_file =
            $this->appRoot.'/sites/default/settings.local.php';

        $this->local_settings_file_original =
            $this->appRoot.'/sites/example.settings.local.php';

        $this->fs = new Filesystem();
        $this->maginot = new Maginot();
        $this->yaml = new Yaml();

        $this->environment = null;

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
            ->addOption(
                'local',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.site.mode.options.local')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->environment = $input->getArgument('environment');
        $this->local = $input->getOption('local');

        $loadedConfigurations = [];
        if (in_array($this->environment, array('dev', 'prod'))) {
            $loadedConfigurations = $this->loadConfigurations($this->environment);
        } else {
            $io->error($this->trans('commands.site.mode.messages.invalid-env'));
        }

        $configurationOverrideResult = $this->overrideConfigurations(
            $loadedConfigurations['configurations'],
            $io
        );

        foreach ($configurationOverrideResult as $configName => $result) {
            $io->info(
                $this->trans('commands.site.mode.messages.configuration').':',
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

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function overrideConfigurations($configurations, $io)
    {
        $result = [];
        foreach ($configurations as $configName => $options) {
            $config = $this->configFactory->getEditable($configName);
            foreach ($options as $key => $value) {
                $original = $config->get($key);
                if (is_bool($original)) {
                    $original = $original ? 'true' : 'false';
                }
                $updated = $value;
                if (is_bool($updated)) {
                    $updated = $updated ? 'true' : 'false';
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

        $line_include_settings =
            '<?php include __DIR__ . "/settings.local.php"; ?>';

        if ($this->environment == 'dev') {

            // copy sites/example.settings.local.php sites/default/settings.local.php
            $this->fs->copy($this->local_settings_file_original, $this->local_settings_file, true);

            // uncomment cache bins in settings.local.php
            $this->maginot->unCommentLine(
                '# $settings[\'cache\'][\'bins\'][\'render\'] = \'cache.backend.null\';',
                $this->local_settings_file
            );

            $this->maginot->unCommentLine(
                '// $settings[\'cache\'][\'bins\'][\'render\'] = \'cache.backend.null\';',
                $this->local_settings_file
            );

            $this->maginot->unCommentLine(
                '# $settings[\'cache\'][\'bins\'][\'dynamic_page_cache\'] = \'cache.backend.null\';',
                $this->local_settings_file
            );

            $this->maginot->unCommentLine(
                '// $settings[\'cache\'][\'bins\'][\'dynamic_page_cache\'] = \'cache.backend.null\';',
                $this->local_settings_file
            );

            // include settings.local.php in settings.php
            // -- check first line if it is already this
            if ($this->maginot->getFirstLine($this->settings_file)!= $line_include_settings
            ) {
                chmod($this->settings_file, (int)0775);
                $this->maginot->setFirstLine(
                    $line_include_settings,
                    $this->settings_file
                );
            }

            $io->commentBlock(
                sprintf(
                    '%s',
                    $this->trans('commands.site.mode.messages.cachebins')
                )
            );
        }
        if ($this->environment == 'prod') {
            if (!$this->local) {

                // comment local.settings.php in settings.php
                if ($this->maginot->getFirstLine($this->settings_file)==$line_include_settings
                ) {
                    $this->maginot->deleteFirstLine(
                        $this->settings_file
                    );
                }


                try {
                    $this->fs->remove(
                        $this->local_settings_file
                    );
                    //@TODO: msg user "local.settings.php deleted"
                } catch (IOExceptionInterface $e) {
                    echo $e->getMessage();
                }
            } else {

                // comment cache bins in local.settings.php,
                // we still use local.settings.php for testing PROD
                // settings in local

                $this->maginot->CommentLine(
                    ' $settings[\'cache\'][\'bins\'][\'render\'] = \'cache.backend.null\';',
                    $this->local_settings_file
                );

                $this->maginot->CommentLine(
                    ' $settings[\'cache\'][\'bins\'][\'dynamic_page_cache\'] = \'cache.backend.null\';',
                    $this->local_settings_file
                );
            }
        }

        /**
         * would be better if this were replaced by $config->save?
         */
        //@TODO: 0444 should be a better permission for settings.php
        chmod($this->settings_file, (int)0644);
        //@TODO: 0555 should be a better permission for sites/default
        chmod($this->appRoot.'/sites/default/', (int)0755);

        return $result;
    }

    protected function overrideServices($servicesSettings, DrupalStyle $io)
    {
        $directory = sprintf(
            '%s/%s',
            $this->appRoot,
            \Drupal::service('site.path')
        );

        $settingsServicesFile = $directory.'/services.yml';
        if (!file_exists($settingsServicesFile)) {
            // Copying default services
            $defaultServicesFile = $this->appRoot.'/sites/default/default.services.yml';
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

        $services = $this->yaml->parse(file_get_contents($settingsServicesFile));

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

        if (file_put_contents($settingsServicesFile, $this->yaml->dump($services))) {
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
                $this->configurationManager->getApplicationDirectory().DRUPAL_CONSOLE_CORE
            );
        }

        $siteModeConfiguration = $this->yaml->parse(file_get_contents($configFile));
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
