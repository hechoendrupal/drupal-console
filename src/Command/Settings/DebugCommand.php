<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Settings\DebugCommand.
 */

namespace Drupal\Console\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\ContainerAwareCommand;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Settings
 */
class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('settings:debug')
            ->setDescription($this->trans('commands.settings.debug.description'))
            ->setHelp($this->trans('commands.settings.debug.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $settings = $this->getSettings();
        $settingKeys = array_keys($settings->getAll());
        $dumper = new Dumper();

        $io->newLine();
        $io->info($this->trans('commands.settings.debug.messages.current'));
        $io->newLine();

        foreach ($settingKeys as $settingKey) {
            $io->comment($settingKey, false);
            $io->simple($dumper->dump($settings->get($settingKey), 10));
        }
        $io->newLine();
    }
}
