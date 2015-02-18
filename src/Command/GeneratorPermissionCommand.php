<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorPermissionCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\PermissionTrait;
use Drupal\AppConsole\Generator\PermissionGenerator;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPermissionCommand extends GeneratorCommand
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
          ->addOption('module', '', InputOption::VALUE_REQUIRED,
            $this->trans('commands.common.options.module'))
          ->addOption('permissions', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.common.options.permissions'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $permissions = $input->getOption('permissions');
        $permission_uc = $input->getOption('permissions');

        $this
          ->getGenerator()
          ->generate($module, $permissions, $permission_uc);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --permissions option
        $permissions = $input->getOption('permissions');
        if (!$permissions) {
            // @see \Drupal\AppConsole\Command\Helper\PermissionTrait::permissionQuestion
            $permissions = $this->permissionQuestion($output, $dialog);
        }
        $input->setOption('permissions', $permissions);
    }

    /**
     * @return \Drupal\AppConsole\Generator\PermissionGenerator.
     */
    protected function createGenerator()
    {
        return new PermissionGenerator();
    }
}
