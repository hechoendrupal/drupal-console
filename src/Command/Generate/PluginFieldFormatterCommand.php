<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldFormatterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldFormatterGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\ChainQueue;

/**
 * Class PluginFieldFormatterCommand
 * @package Drupal\Console\Command\Generate
 */
class PluginFieldFormatterCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /**
 * @var Manager  
*/
    protected $extensionManager;

    /**
 * @var PluginFieldFormatterGenerator  
*/
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
 * @var FieldTypePluginManager  
*/
    protected $fieldTypePluginManager;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginImageFormatterCommand constructor.
     * @param Manager                       $extensionManager
     * @param PluginFieldFormatterGenerator $generator
     * @param StringConverter               $stringConverter
     * @param FieldTypePluginManager        $fieldTypePluginManager
     * @param ChainQueue                    $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginFieldFormatterGenerator $generator,
        StringConverter $stringConverter,
        FieldTypePluginManager $fieldTypePluginManager,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->fieldTypePluginManager = $fieldTypePluginManager;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldformatter')
            ->setDescription($this->trans('commands.generate.plugin.fieldformatter.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldformatter.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldformatter.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldformatter.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldformatter.options.plugin-id')
            )
            ->addOption(
                'field-type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldformatter.options.field-type')
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
        $field_type = $input->getOption('field-type');

        $this->generator->generate($module, $class_name, $label, $plugin_id, $field_type);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
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
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.class'),
                'ExampleFieldFormatter'
            );
            $input->setOption('class', $class);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.label'),
                $this->stringConverter->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --name option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --field type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            // Gather valid field types.
            $field_type_options = [];
            foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
                foreach ($field_types as $name => $field_type) {
                    $field_type_options[] = $name;
                }
            }

            $field_type = $io->choice(
                $this->trans('commands.generate.plugin.fieldwidget.questions.field-type'),
                $field_type_options
            );

            $input->setOption('field-type', $field_type);
        }
    }
}
