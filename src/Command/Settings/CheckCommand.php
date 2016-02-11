<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Settings\CheckCommand.
 */

namespace Drupal\Console\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class CheckCommand
 * @package Drupal\Console\Command\Settings
 */
class CheckCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('settings:check')
            ->setDescription($this->trans('commands.settings.check.description'))
            ->setAliases(['check']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $requirementChecker = $this->getContainerHelper()->get('requirement_checker');
        $requirementChecker->validate($this->getApplication()->getDirectoryRoot().'/requirements.yml');
        $checks = $requirementChecker->getCheckResult();

        if (!$checks['php']['valid']) {
            $io->error(
                sprintf(
                    $this->trans('commands.settings.check.messages.php_invalid'),
                    $checks['php']['current'],
                    $checks['php']['required']
                )
            );
        }

        if ($extensions = $checks['extensions']['required']['missing']) {
            foreach ($extensions as $extension) {
                $io->error(
                    sprintf(
                        $this->trans('commands.settings.check.messages.extension_missing'),
                        $extension
                    )
                );
            }
        }

        if ($extensions = $checks['extensions']['recommended']['missing']) {
            foreach ($extensions as $extension) {
                $io->commentBlock(
                    sprintf(
                        $this->trans(
                            'commands.settings.check.messages.extension_recommended'
                        ),
                        $extension
                    )
                );
            }
        }

        if ($configurations = $checks['configurations']['required']['missing']) {
            foreach ($configurations as $configuration) {
                $io->error(
                    sprintf(
                        $this->trans('commands.settings.check.messages.configuration_missing'),
                        $configuration
                    )
                );
            }
        }

        if ($configurations = $checks['configurations']['required']['overwritten']) {
            foreach ($configurations as $configuration => $overwritten) {
                $io->commentBlock(
                    sprintf(
                        $this->trans(
                            'commands.settings.check.messages.configuration_overwritten'
                        ),
                        $configuration,
                        $overwritten
                    )
                );
            }
        }

        if ($requirementChecker->isValid()) {
            $io->success(
                $this->trans('commands.settings.check.messages.success')
            );
        }

        return $requirementChecker->isValid();
    }
}
