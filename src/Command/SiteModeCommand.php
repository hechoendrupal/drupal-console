<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\SiteModeCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Site\Settings;

class SiteModeCommand extends ContainerAwareCommand
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
        $table = $this->getHelperSet()->get('table');
        $environment = $input->getArgument('environment');

        $configurationOverrideResult = [];

        if (in_array($environment, array('dev', 'prod'))) {
            $configurationOverrideResult = $this->overrideConfigurations($environment, $output);
        } else {
            $output->writeln(
                ' <error>'.$this->trans('commands.site.mode.messages.invalid-env').'</error>'
            );
        }

        foreach ($configurationOverrideResult as $configName => $result) {
            $output->writeln(
                sprintf(
                    ' <info>%s:</info> <comment>%s</comment>',
                    $this->trans('commands.site.mode.messages.configuration'),
                    $configName
                )
            );

            $table->setHeaders(
                [
                    $this->trans('commands.site.mode.messages.configuration-key'),
                    $this->trans('commands.site.mode.messages.original'),
                    $this->trans('commands.site.mode.messages.updated'),
                ]
            );
            $table->setlayout($table::LAYOUT_COMPACT);
            $table->setRows($result);
            $table->render($output);
            print "\n";
        }

        $servicesOverrideResult = $this->overrideServices($environment, $output);

        if (!empty($servicesOverrideResult)) {
            $output->writeln(
                ' <info>' .  $this->trans('commands.site.mode.messages.new-services-settings') . '</info>'
            );

            $table->setHeaders(
                [
                    $this->trans('commands.site.mode.messages.service'),
                    $this->trans('commands.site.mode.messages.service-parameter'),
                    $this->trans('commands.site.mode.messages.service-value'),
                ]
            );
            $table->setlayout($table::LAYOUT_COMPACT);
            $table->setRows($servicesOverrideResult);
            $table->render($output);
        }

        $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function overrideConfigurations($env)
    {
        $result = [];
        $configurations = $this->getConfigurations($env);
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

    protected function overrideServices($env, $output)
    {
        $services_settings = $this->getServicesSettings($env);

        $directory = DRUPAL_ROOT . '/' .  \Drupal::service('site.path');

        $settings_services_file = $directory . '/services.yml';
        if (!file_exists($settings_services_file)) {
            // Copying default services
            $default_services_file = DRUPAL_ROOT . '/sites/default/default.services.yml';
            if (!copy($default_services_file, $directory . '/services.yml')) {
                $output->writeln(
                    ' <error>'. $this->trans('commands.site.mode.messages.error-copying-file') . ': ' . $directory . '/services.yml' .'</error>'
                );
                return [];
            }
        }

        $yaml = new \Symfony\Component\Yaml\Yaml();
        $content = file_get_contents($directory . '/services.yml');
        $services = $yaml->parse($content);

        $result = [];
        foreach ($services_settings as $service => $parameters) {
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

        if (file_put_contents($directory . '/services.yml', $yaml->dump($services))) {
            $output->writeln(
                '<info>' . sprintf($this->trans('commands.site.mode.messages.services-file-overwritten'), $directory . '/services.yml') . '</info>'
            );
        } else {
            $output->writeln(
                ' <error>'. $this->trans('commands.site.mode.messages.error-writing-file') . ': ' . $directory . '/services.yml' .'</error>'
            );
            return [];
        }

        sort($result);
        return $result;
    }

    protected function getConfigurations($env)
    {
        $settings =  [
            'system.performance' => array(
                'cache.page.use_internal' => array('dev' => false, 'prod' => true),
                'css.preprocess' => array('dev' => false, 'prod' => true),
                'css.gzip' => array('dev' => false, 'prod' => true),
                'js.preprocess' => array('dev' => false, 'prod' => true),
                'js.gzip' => array('dev' => false, 'prod' => true),
                'response.gzip' => array('dev' => false, 'prod' => true),
            ),
            'views.settings' => array(
                'ui.show.sql_query.enabled' => array('dev' => true, 'prod' => false),
                'ui.show.performance_statistics' => array('dev' => true, 'prod' => false),
            ),
            'system.logging' => array(
                'error_level' => array('dev' => ERROR_REPORTING_DISPLAY_ALL, 'prod' => ERROR_REPORTING_HIDE),
            )
        ];

        $configuration_settings = [];
        foreach ($settings as $setting => $parameters) {
            foreach ($parameters as $parameter => $value) {
                $configuration_settings[$setting][$parameter] = $value[$env];
            }
        }

        return $configuration_settings;
    }

    protected function getServicesSettings($env)
    {
        $settings = [
            'twig.config' => [
                'debug' => ['dev' => true, 'prod' => false],
                'auto_reload' =>['dev' => true, 'prod' => false],
                'cache' => ['dev' => true, 'prod' => false]
            ]
        ];

        $environment_settings = [];
        foreach ($settings as $setting => $parameters) {
            foreach ($parameters as $parameter => $value) {
                $environment_settings[$setting][$parameter] = $value[$env];
            }
        }

        return $environment_settings;
    }
}
