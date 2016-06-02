<?php

/**
 * @file
 * Contains \Drupal\Console\Command\CheckCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class CheckCommand
 * @package Drupal\Console\Command
 */
class CheckCommand extends BaseCommand
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription($this->trans('commands.check.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $requirementChecker = $this->get('requirement_checker');
        $checks = $requirementChecker->getCheckResult();
        if (!$checks) {
            $phpCheckFile = $this->getApplication()->getConfig()->getUserHomeDir().'/.console/phpcheck.yml';
            if (!file_exists($phpCheckFile)) {
                $phpCheckFile = $this->getApplication()->getDirectoryRoot().'config/dist/phpcheck.yml';
            }
            $requirementChecker->validate($phpCheckFile);
            $checks = $requirementChecker->validate($phpCheckFile);
        }

        if (!$checks['php']['valid']) {
            $io->error(
                sprintf(
                    $this->trans('commands.check.messages.php_invalid'),
                    $checks['php']['current'],
                    $checks['php']['required']
                )
            );
        }

        if ($extensions = $checks['extensions']['required']['missing']) {
            foreach ($extensions as $extension) {
                $io->error(
                    sprintf(
                        $this->trans('commands.check.messages.extension_missing'),
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
                            'commands.check.messages.extension_recommended'
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
                        $this->trans('commands.check.messages.configuration_missing'),
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
                            'commands.check.messages.configuration_overwritten'
                        ),
                        $configuration,
                        $overwritten
                    )
                );
            }
        }

        if ($requirementChecker->isValid() && !$requirementChecker->isOverwritten()) {
            $io->success(
                $this->trans('commands.check.messages.success')
            );
            $this->get('chain_queue')->addCommand(
                'settings:set',
                [
                    'setting-name' => 'checked',
                    'setting-value' => 'true',
                    '--quiet'
                ]
            );
        }

        return $requirementChecker->isValid();
    }
}
