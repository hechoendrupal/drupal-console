<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\SettingsDebugCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Core\Site\Settings;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Config
 */
class SettingsDebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * SettingsDebugCommand constructor.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        ;
        parent::__construct();
    }
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

        $settingKeys = array_keys($this->settings->getAll());

        $io->newLine();
        $io->info($this->trans('commands.config.settings.debug.messages.current'));
        $io->newLine();

        foreach ($settingKeys as $settingKey) {
            $io->comment($settingKey, false);
            $io->simple(Yaml::encode($this->settings->get($settingKey)));
        }
        $io->newLine();
    }
}
