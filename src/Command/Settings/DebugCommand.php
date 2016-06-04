<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Settings\DebugCommand.
 */

namespace Drupal\Console\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Settings
 */
class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('settings:debug')
            ->setDescription($this->trans('commands.settings.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $nestedArray = $this->getApplication()->getNestedArrayHelper();

        $application = $this->getApplication();
        $config = $application->getConfig();

        $configApplication = $config->get('application');

        unset($configApplication['autowire']);
        unset($configApplication['languages']);
        unset($configApplication['aliases']);
        unset($configApplication['default']['commands']);

        $configApplicationFlatten = [];
        $keyFlatten = '';
        $nestedArray->yamlFlattenArray($configApplication, $configApplicationFlatten, $keyFlatten);

        $tableHeader = [
            $this->trans('commands.settings.debug.messages.config-key'),
            $this->trans('commands.settings.debug.messages.config-value'),
        ];

        $tableRows = [];
        foreach ($configApplicationFlatten as $yamlKey => $yamlValue) {
            $tableRows[] = [
                $yamlKey,
                $yamlValue
            ];
        }

        $io->newLine();
        $io->info(
            sprintf(
                '%s :',
                $this->trans('commands.settings.debug.messages.config-file')
            ),
            false
        );

        $io->comment(
            sprintf('%s/.console/config.yml', $config->getUserHomeDir()),
            true
        );

        $io->newLine();

        $io->table($tableHeader, $tableRows, 'compact');
    }
}
