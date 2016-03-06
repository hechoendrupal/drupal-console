<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\OverrideCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class OverrideCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('config:override')
            ->setDescription($this->trans('commands.config.override.description'))
            ->addArgument(
                'config-name',
                InputArgument::REQUIRED,
                $this->trans('commands.config.override.arguments.config-name')
            )
            ->addArgument('key', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.key'))
            ->addArgument('value', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.value'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('config-name');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $config = $this->getConfigFactory()->getEditable($configName);

        $configurationOverrideResult = $this->overrideConfiguration($config, $key, $value);

        $config->save();

        $io->info($this->trans('commands.config.override.messages.configuration'), false);
        $io->comment($configName);

        $tableHeader = [
            $this->trans('commands.config.override.messages.configuration-key'),
            $this->trans('commands.config.override.messages.original'),
            $this->trans('commands.config.override.messages.updated'),
        ];
        $tableRows = $configurationOverrideResult;
        $io->table($tableHeader, $tableRows);

        $config->save();
    }

    protected function overrideConfiguration($config, $key, $value)
    {
        $result[] = [
          'configuration' => $key,
          'original' => $config->get($key),
          'updated' => $value,
        ];
        $config->set($key, $value);

        return $result;
    }
}
