<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldTypeCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldTypeGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Core\Field\FieldTypePluginManager;

/**
 * Class PluginFieldTypeCommand
 * @package Drupal\Console\Command\Generate
 */
class PluginFieldTypeCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /**
 * @var Manager  
*/
    protected $extensionManager;

    /**
 * @var PluginFieldTypeGenerator  
*/
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginFieldTypeCommand constructor.
     * @param Manager                  $extensionManager
     * @param PluginFieldTypeGenerator $generator
     * @param StringConverter          $stringConverter
     * @param ChainQueue               $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginFieldTypeGenerator $generator,
        StringConverter $stringConverter,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldtype')
            ->setDescription($this->trans('commands.generate.plugin.fieldtype.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldtype.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldtype.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.plugin-id')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.description')
            )
            ->addOption(
                'default-widget',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.default-widget')
            )
            ->addOption(
                'default-formatter',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.default-formatter')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $description = $input->getOption('description');
        $default_widget = $input->getOption('default-widget');
        $default_formatter = $input->getOption('default-formatter');

        $this->generator
            ->generate($module, $class_name, $label, $plugin_id, $description, $default_widget, $default_formatter);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery'], false);
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
                $this->trans('commands.generate.plugin.fieldtype.questions.class'),
                'ExampleFieldType'
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.fieldtype.questions.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.fieldtype.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.plugin.fieldtype.questions.description'),
                'My Field Type'
            );
            $input->setOption('description', $description);
        }

        // --default-widget option
        $default_widget = $input->getOption('default-widget');
        if (!$default_widget) {
            $default_widget = $io->askEmpty(
                $this->trans('commands.generate.plugin.fieldtype.questions.default-widget')
            );
            $input->setOption('default-widget', $default_widget);
        }

        // --default-formatter option
        $default_formatter = $input->getOption('default-formatter');
        if (!$default_formatter) {
            $default_formatter = $io->askEmpty(
                $this->trans('commands.generate.plugin.fieldtype.questions.default-formatter')
            );
            $input->setOption('default-formatter', $default_formatter);
        }
    }
}
