<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\PermissionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\PermissionTrait;
use Drupal\Console\Generator\PermissionGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;

class PermissionCommand extends Command
{
    use ArrayInputTrait;
    use ModuleTrait;
    use PermissionTrait;

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
     * @var Validator
     */
    protected $validator;

    /**
     * PermissionCommand constructor.
     *
     * @param Manager         $extensionManager
     * @param StringConverter $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        StringConverter $stringConverter,
        PermissionGenerator $permissionGenerator,
        Validator $validator
    ) {
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->generator = $permissionGenerator;
        $this->validator = $validator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:permissions')
            ->setDescription($this->trans('commands.generate.permissions.description'))
            ->setHelp($this->trans('commands.generate.permissions.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'permissions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.permissions')
            )
            ->setAliases(['gp']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $this->validateModule($input->getOption('module'));
        $permissions = $input->getOption('permissions');
        $learning = $input->hasOption('learning');
        $noInteraction = $input->getOption('no-interaction');
        // Parse nested data.
        if ($noInteraction) {
          $permissions = $this->explodeInlineArray($permissions);
        }

        $this->generator->generate([
          'module_name' => $module,
          'permissions' => $permissions,
          'learning' => $learning,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --permissions option
        $permissions = $input->getOption('permissions');
        if (!$permissions) {
            // @see \Drupal\Console\Command\Shared\PermissionTrait::permissionQuestion
            $permissions = $this->permissionQuestion();
        } else {
            $permissions = $this->explodeInlineArray($permissions);
        }

        $input->setOption('permissions', $permissions);
    }
}
