<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\PermissionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\PermissionTrait;
use Drupal\Console\Generator\PermissionGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PermissionCommand extends GeneratorCommand
{
    use ModuleTrait;
    use PermissionTrait;
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:permissions')
            ->setDescription($this->trans('commands.generate.permission.description'))
            ->setHelp($this->trans('commands.generate.permission.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'permissions',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.common.options.permissions')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $permissions = $input->getOption('permissions');

        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $permissions);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --permissions option
        $permissions = $input->getOption('permissions');
        if (!$permissions) {
            // @see \Drupal\Console\Command\Shared\PermissionTrait::permissionQuestion
            $permissions = $this->permissionQuestion($output);
            $input->setOption('permissions', $permissions);
        }
    }

    /**
     * @return \Drupal\Console\Generator\PermissionGenerator.
     */
    protected function createGenerator()
    {
        return new PermissionGenerator();
    }
}
