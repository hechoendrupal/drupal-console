<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\SiteModeCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            $configurationOverrideResult = $this->overrideConfigurations($environment);
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

        $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function overrideConfigurations($env)
    {
        $result = [];
        $configurations = $this->getConfigurations();
        foreach ($configurations as $configName => $options) {
            $config = $this->getConfigFactory()->getEditable($configName);
            foreach ($options as $key => $value) {
                $result[$configName][] = [
                    'configuration' => $key,
                    'original' => $config->get($key) ? 'true' : 'false',
                    'updated' => $value[$env]  ? 'true' : 'false',
                ];
                $config->set($key, $value[$env]);
            }
            $config->save();
        }

        return $result;
    }

    protected function getConfigurations()
    {
        return [
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
        ];
    }
}
