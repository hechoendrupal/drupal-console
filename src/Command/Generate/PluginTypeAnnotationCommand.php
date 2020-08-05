<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeAnnotationCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeAnnotationGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginTypeAnnotationCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginTypeAnnotationCommand extends Command
{
    use ModuleTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginTypeAnnotationGenerator
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
     * PluginTypeAnnotationCommand constructor.
     *
     * @param Manager                       $extensionManager
     * @param PluginTypeAnnotationGenerator $generator
     * @param StringConverter               $stringConverter
     * @param Validator                     $validator
     */
    public function __construct(
        Manager $extensionManager,
        PluginTypeAnnotationGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:type:annotation')
            ->setDescription($this->trans('commands.generate.plugin.type.annotation.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.annotation.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.class')
            )
            ->addOption(
                'machine-name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.plugin-id')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.label')
            )
            ->setAliases(['gpta']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $this->validateModule($input->getOption('module'));
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $machine_name = $input->getOption('machine-name');
        $label = $input->getOption('label');

        $this->generator->generate([
            'module' => $module,
            'class_name' => $class_name,
            'machine_name' => $machine_name,
            'label' => $label,
        ]);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.class'),
                'ExamplePlugin',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class', $class_name);
        }

        // --machine-name option
        $machine_name = $input->getOption('machine-name');
        if (!$machine_name) {
            $machine_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.machine-name'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('machine-name', $machine_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }
    }
}
