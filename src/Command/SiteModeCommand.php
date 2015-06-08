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
          ->addArgument('environment', InputArgument::REQUIRED,
            $this->trans('commands.site.mode.arguments.environment'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $this->getHelperSet()->get('table');
        $environment = $input->getArgument('environment');
        $configName = 'system.performance';
        $config = $this->getConfigFactory()->getEditable($configName);
        $configurationOverrideResult = [];

        if ('dev' === $environment) {
            $configurationOverrideResult = $this->overrideConfigurations($config, false);
        }
        if ('prod' === $environment) {
            $configurationOverrideResult = $this->overrideConfigurations($config, true);
        }

        $config->save();

        $output->writeln(sprintf(
          ' <info>%s:</info> <comment>%s</comment>',
          $this->trans('commands.site.mode.messages.configuration'),
          $configName
        ));

        $table->setHeaders(
          [
            $this->trans('commands.site.mode.messages.configuration-key'),
            $this->trans('commands.site.mode.messages.original'),
            $this->trans('commands.site.mode.messages.updated'),
          ]);
        $table->setlayout($table::LAYOUT_COMPACT);
        $table->setRows($configurationOverrideResult);
        $table->render($output);
    }

    protected function overrideConfigurations($config, $value)
    {
        $result = [];
        $configurations = $this->getConfigurations();
        foreach ($configurations as $configuration) {
            $result[] = [
              'configuration' => $configuration,
              'original' => $config->get($configuration) ? 'true' : 'false',
              'updated' => $value ? 'true' : 'false',
            ];
            $config->set($configuration, $value);
        }

        return $result;
    }

    protected function getConfigurations()
    {
        return [
          'cache.page.use_internal',
          'css.preprocess',
          'css.gzip',
          'js.preprocess',
          'js.gzip',
          'response.gzip',
        ];
    }
}
