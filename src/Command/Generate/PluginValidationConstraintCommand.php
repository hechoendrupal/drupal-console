<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginValidationConstraintCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginValidationConstraintGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class PluginValidationConstraintCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginValidationConstraintCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var PluginValidationConstraintGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginValidationConstraintCommand constructor.
     *
     * @param Manager                             $extensionManager
     * @param PluginValidationConstraintGenerator $generator
     * @param StringConverter                     $stringConverter
     * @param Validator                           $validator
     * @param ChainQueue                          $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginValidationConstraintGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:validationconstraint')
            ->setDescription($this->trans('commands.generate.plugin.validationconstraint.description'))
            ->setHelp($this->trans('commands.generate.plugin.validationconstraint.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.validationconstraint.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.validationconstraint.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.validationconstraint.options.plugin-id')
            )
            ->addOption(
                'hook',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.validationconstraint.options.hook')
            )
            ->addOption(
                'field-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.validationconstraint.options.field-id')
            )
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.validationconstraint.options.bundle')
            )
            ->setAliases(['gpvc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $input->getOption('module');
        $className = $this->validator->validateClassName($input->getOption('class'));
        $label = $input->getOption('label');
        $pluginId = $input->getOption('plugin-id');
        $hook = $input->getOption('hook');
        $fieldId = $input->getOption('field-id');
        $bundle = $input->getOption('bundle');

        $this->generator->generate([
            'module' => $module,
            'class_name' => $className,
            'label' => $label,
            'plugin_id' => $pluginId,
            'field_id' => $fieldId,
            'hook' => $hook,
            'bundle' => $bundle,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $className = $input->getOption('class');
        if (!$className) {
            $className = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.validationconstraint.questions.class'),
                'ExampleConstraint',
                function ($className) {
                    return $this->validator->validateClassName($className);
                }
            );
            $input->setOption('class', $className);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.validationconstraint.questions.label'),
                $this->stringConverter->camelCaseToHuman($className)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.validationconstraint.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($className)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        $hook = $this->getIo()->confirm(
          $this->trans('commands.generate.plugin.validationconstraint.questions.hook'),
          false
        );
        if (!empty($hook)) {
            $fieldId = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.validationconstraint.questions.field-id')
            );
            $input->setOption('field-id', $fieldId);

            $bundle = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.validationconstraint.questions.bundle')
            );
            $input->setOption('bundle', $bundle);
        }
        $input->setOption('hook', $hook);
    }
}
