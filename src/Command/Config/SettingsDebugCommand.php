<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\SettingsDebugCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Config
 */
class SettingsDebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:settings:debug')
            ->setDescription($this->trans('commands.config.settings.debug.description'))
            ->setHelp($this->trans('commands.config.settings.debug.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $settings = $this->getDrupalService('settings');
        $settingKeys = array_keys($settings->getAll());
        $dumper = new Dumper();

        $io->newLine();
        $io->info($this->trans('commands.config.settings.debug.messages.current'));
        $io->newLine();

        foreach ($settingKeys as $settingKey) {
            $io->comment($settingKey, false);
            $io->simple($dumper->dump($settings->get($settingKey), 10));
        }
        $io->newLine();
    }
}
