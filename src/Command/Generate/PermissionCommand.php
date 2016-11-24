<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\PermissionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\PermissionTrait;
use Drupal\Console\Generator\PermissionGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\StringConverter;

class PermissionCommand extends Command
{
    use CommandTrait;
    use ModuleTrait;
    use PermissionTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var PermissionGenerator;
     */
    protected $generator;

    /**
     * PermissionCommand constructor.
     * @param Manager         $extensionManager
     * @param StringConverter $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        StringConverter $stringConverter,
        PermissionGenerator $permissionGenerator
    ) {
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->generator = $permissionGenerator;
        parent::__construct();
    }

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
        $learning = $input->hasOption('learning');


        $this->generator->generate($module, $permissions, $learning);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --permissions option
        $permissions = $input->getOption('permissions');
        if (!$permissions) {
            // @see \Drupal\Console\Command\Shared\PermissionTrait::permissionQuestion
            $permissions = $this->permissionQuestion($io);
            $input->setOption('permissions', $permissions);
        }
    }
}
