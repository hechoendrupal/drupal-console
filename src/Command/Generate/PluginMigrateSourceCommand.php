<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginMigrateSourceCommand.
 */

namespace Drupal\Console\Command\Generate;


use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Command\Shared\ArrayInputTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\PluginMigrateSourceGenerator;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginMigrateSourceCommand extends Command
{
    use ArrayInputTrait;
    use ConfirmationTrait;
    use ModuleTrait;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var PluginMigrateSourceGenerator
     */
    protected $generator;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ElementInfoManagerInterface
     */
    protected $elementInfoManager;

    /**
     * PluginMigrateSourceCommand constructor.
     *
     * @param ConfigFactory               $configFactory
     * @param ChainQueue                  $chainQueue
     * @param PluginBlockGenerator        $generator
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param Manager                     $extensionManager
     * @param Validator                   $validator
     * @param StringConverter             $stringConverter
     * @param ElementInfoManagerInterface $elementInfoManager
     */
    public function __construct(
        ConfigFactory $configFactory,
        ChainQueue $chainQueue,
        PluginMigrateSourceGenerator $generator,
        EntityTypeManagerInterface $entityTypeManager,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        ElementInfoManagerInterface $elementInfoManager
    ) {
        $this->configFactory = $configFactory;
        $this->chainQueue = $chainQueue;
        $this->generator = $generator;
        $this->entityTypeManager = $entityTypeManager;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->elementInfoManager = $elementInfoManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:migrate:source')
            ->setDescription($this->trans('commands.generate.plugin.migrate.source.description'))
            ->setHelp($this->trans('commands.generate.plugin.migrate.source.help'))
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
                $this->trans('commands.generate.plugin.migrate.source.options.class')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.source.options.plugin-id')
            )
            ->addOption(
                'table',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.source.options.table')
            )
            ->addOption(
                'alias',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.source.options.alias')
            )
            ->addOption(
                'group-by',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.source.options.group-by')
            )
            ->addOption(
                'fields',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.migrate.source.options.fields')
            )->setAliases(['gpms']);
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

        $module = $this->validateModule($input->getOption('module'));
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $plugin_id = $input->getOption('plugin-id');
        $table = $input->getOption('table');
        $alias = $input->getOption('alias');
        $group_by = $input->getOption('group-by');
        $fields = $input->getOption('fields');
        $no_interaction = $input->getOption('no-interaction');
        // Parse nested data.
        if ($no_interaction) {
            $fields = $this->explodeInlineArray($fields);
        }

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'plugin_id' => $plugin_id,
          'table' => $table,
          'alias' => $alias,
          'group_by' => $group_by,
          'fields' => $fields,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // 'module-name' option.
        $module = $this->getModuleOption();

        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.class'),
                ucfirst($this->stringConverter->underscoreToCamelCase($module)),
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        $table = $input->getOption('table');
        if (!$table) {
            $table = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.table'),
                ''
            );
            $input->setOption('table', $table);
        }

        $alias = $input->getOption('alias');
        if (!$alias) {
            $alias = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.alias'),
                substr($table, 0, 1)
            );
            $input->setOption('alias', $alias);
        }

        $groupBy = $input->getOption('group-by');
        if ($groupBy == '') {
            $groupBy = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.group-by'),
                false
            );
            $input->setOption('group-by', $groupBy);
        }

        $fields = $input->getOption('fields');
        if (!$fields) {
            $fields = [];
            while (true) {
                $id = $this->getIo()->ask(
                    $this->trans('commands.generate.plugin.migrate.source.questions.id'),
                    false
                );
                if (!$id) {
                    break;
                }
                $description = $this->getIo()->ask(
                    $this->trans('commands.generate.plugin.migrate.source.questions.description'),
                    $id
                );
                $fields[] = [
                    'id' => $id,
                    'description' => $description,
                ];
            }
            $input->setOption('fields', $fields);
        }
    }
}
