<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ConfigSettingsCommand.
 */

namespace Drupal\Console\Command\Debug;

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
 * @package Drupal\Console\Command\Debug
 */
class ConfigSettingsCommand extends Command
{
    use CommandTrait;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * ConfigSettingsCommand constructor.
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
            ->setName('debug:config:settings')
            ->setDescription($this->trans('commands.debug.config.settings.description'))
            ->setHelp($this->trans('commands.debug.config.settings.help'))
            ->setAliases(['dcs']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $settingKeys = array_keys($this->settings->getAll());

        $io->newLine();
        $io->info($this->trans('commands.debug.config.settings.messages.current'));
        $io->newLine();

        foreach ($settingKeys as $settingKey) {
            $settingValue = $this->settings->get($settingKey);
            $io->comment($settingKey . ': ', is_array($settingValue));
            $io->write(Yaml::encode($settingValue));
            if (!is_array($settingValue)) {
                $io->newLine();
            }
        }
        $io->newLine();
    }
}
