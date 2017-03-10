<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeAnnotationCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeAnnotationGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginTypeAnnotationCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginTypeAnnotationCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;
    use CommandTrait;

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
     * PluginTypeAnnotationCommand constructor.
     *
     * @param Manager                       $extensionManager
     * @param PluginTypeAnnotationGenerator $generator
     * @param StringConverter               $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        PluginTypeAnnotationGenerator $generator,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:type:annotation')
            ->setDescription($this->trans('commands.generate.plugin.type.annotation.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.annotation.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.class')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.plugin-id')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.label')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $machine_name = $input->getOption('machine-name');
        $label = $input->getOption('label');

        $this->generator->generate($module, $class_name, $machine_name, $label);
    }

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

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.class'),
                'ExamplePlugin'
            );
            $input->setOption('class', $class_name);
        }

        // --machine-name option
        $machine_name = $input->getOption('machine-name');
        if (!$machine_name) {
            $machine_name = $io->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.machine-name'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('machine-name', $machine_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }
    }
}
