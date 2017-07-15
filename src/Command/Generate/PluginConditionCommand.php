<?php

/**
 * @file
 * Contains \Drupal\Console\Command\PluginConditionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Generator\PluginConditionGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginConditionCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginConditionCommand extends Command
{
    use CommandTrait;
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginConditionGenerator
*/
    protected $generator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var StringConverter
     */
    protected $stringConverter;


    /**
     * PluginConditionCommand constructor.
     *
     * @param Manager                  $extensionManager
     * @param PluginConditionGenerator $generator
     * @param ChainQueue               $chainQueue
     * @param EntityTypeRepository     $entitytyperepository
     * @param StringConverter          $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        PluginConditionGenerator $generator,
        ChainQueue $chainQueue,
        EntityTypeRepository $entitytyperepository,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        $this->entitytyperepository = $entitytyperepository;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:condition')
            ->setDescription($this->trans('commands.generate.plugin.condition.description'))
            ->setHelp($this->trans('commands.generate.plugin.condition.help'))
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.plugin-id')
            )
            ->addOption(
                'context-definition-id',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.context-definition-id')
            )
            ->addOption(
                'context-definition-label',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.context-definition-label')
            )
            ->addOption(
                'context-definition-required',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.condition.options.context-definition-required')
            )
            ->setAliases(['gpco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $context_definition_id = $input->getOption('context-definition-id');
        $context_definition_label = $input->getOption('context-definition-label');
        $context_definition_required = $input->getOption('context-definition-required')?'TRUE':'FALSE';

        $this
            ->generator
            ->generate($module, $class_name, $label, $plugin_id, $context_definition_id, $context_definition_label, $context_definition_required);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $entityTypeRepository = $this->entitytyperepository;

        $entity_types = $entityTypeRepository->getEntityTypeLabels(true);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
        }
        $input->setOption('module', $module);

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.class'),
                'ExampleCondition'
            );
            $input->setOption('class', $class);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.label'),
                $this->stringConverter->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        $context_definition_id = $input->getOption('context-definition-id');
        if (!$context_definition_id) {
            $context_type = ['language' => 'Language', "entity" => "Entity"];
            $context_type_sel = $io->choice(
                $this->trans('commands.generate.plugin.condition.questions.context-type'),
                array_values($context_type)
            );
            $context_type_sel = array_search($context_type_sel, $context_type);

            if ($context_type_sel == 'language') {
                $context_definition_id = $context_type_sel;
                $context_definition_id_value = ucfirst($context_type_sel);
            } else {
                $content_entity_types_sel = $io->choice(
                    $this->trans('commands.generate.plugin.condition.questions.context-entity-type'),
                    array_keys($entity_types)
                );

                $contextDefinitionIdList = $entity_types[$content_entity_types_sel];
                $context_definition_id_sel = $io->choice(
                    $this->trans('commands.generate.plugin.condition.questions.context-definition-id'),
                    array_values($contextDefinitionIdList)
                );

                $context_definition_id_value = array_search(
                    $context_definition_id_sel,
                    $contextDefinitionIdList
                );

                $context_definition_id = 'entity:' . $context_definition_id_value;
            }
            $input->setOption('context-definition-id', $context_definition_id);
        }

        $context_definition_label = $input->getOption('context-definition-label');
        if (!$context_definition_label) {
            $context_definition_label = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.context-definition-label'),
                $context_definition_id_value?:null
            );
            $input->setOption('context-definition-label', $context_definition_label);
        }

        $context_definition_required = $input->getOption('context-definition-required');
        if (empty($context_definition_required)) {
            $context_definition_required = $io->confirm(
                $this->trans('commands.generate.plugin.condition.questions.context-definition-required'),
                true
            );
            $input->setOption('context-definition-required', $context_definition_required);
        }
    }
}
