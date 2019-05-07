<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginMigrateProcessCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Generator\PluginMigrateProcessGenerator;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginMigrateProcessCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var PluginMigrateProcessGenerator
     */
    protected $generator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * PluginBlockCommand constructor.
     *
     * @param PluginMigrateProcessGenerator $generator
     * @param ChainQueue                    $chainQueue
     * @param Manager                       $extensionManager
     * @param StringConverter               $stringConverter
     * @param Validator                     $validator
     */
    public function __construct(
        PluginMigrateProcessGenerator $generator,
        ChainQueue $chainQueue,
        Manager $extensionManager,
        StringConverter $stringConverter,
        Validator $validator
    ) {
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:migrate:process')
            ->setDescription($this->trans('commands.generate.plugin.migrate.process.description'))
            ->setHelp($this->trans('commands.generate.plugin.migrate.process.help'))
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
                $this->trans('commands.generate.plugin.migrate.process.options.class')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.process.options.plugin-id')
            )->setAliases(['gpmp']);
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

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'plugin_id' => $plugin_id,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // 'module-name' option.
        $module = $this->getModuleOption();

        // 'class-name' option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.process.questions.class'),
                ucfirst($this->stringConverter->underscoreToCamelCase($module)),
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // 'plugin-id' option.
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }
    }
}
