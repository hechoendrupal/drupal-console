<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginBlockCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Generator\PluginMigrateSourceGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;

class PluginMigrateSourceCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

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
     * PluginBlockCommand constructor.
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
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $module = $input->getOption('module');
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $plugin_id = $input->getOption('plugin-id');
        $table = $input->getOption('table');
        $alias = $input->getOption('alias');
        $group_by = $input->getOption('group-by');
        $fields = $input->getOption('fields');

        $this->generator
            ->generate(
                $module,
                $class_name,
                $plugin_id,
                $table,
                $alias,
                $group_by,
                $fields
            );

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
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
            $pluginId = $io->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        $table = $input->getOption('table');
        if (!$table) {
            $table = $io->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.table'),
                ''
            );
            $input->setOption('table', $table);
        }

        $alias = $input->getOption('alias');
        if (!$alias) {
            $alias = $io->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.alias'),
                substr($table, 0, 1)
            );
            $input->setOption('alias', $alias);
        }

        $groupBy = $input->getOption('group-by');
        if ($groupBy == '') {
            $groupBy = $io->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.group-by'),
                false
            );
            $input->setOption('group-by', $groupBy);
        }

        $fields = $input->getOption('fields');
        if (!$fields) {
            $fields = [];
            while (true) {
                $id = $io->ask(
                    $this->trans('commands.generate.plugin.migrate.source.questions.id'),
                    false
                );
                if (!$id) {
                    break;
                }
                $description = $io->ask(
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
