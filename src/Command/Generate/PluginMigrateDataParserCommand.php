<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginMigrateDataParserCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Generator\PluginMigrateDataParserGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

class PluginMigrateDataParserCommand extends ContainerAwareCommand
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
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * PluginMigrateDataParserGenerator constructor.
     *
     * @param PluginMigrateDataParserGenerator $generator
     * @param ChainQueue                       $chainQueue
     * @param StringConverter                  $stringConverter
     * @param Validator                        $validator
     */
    public function __construct(
      PluginMigrateDataParserGenerator $generator,
      ChainQueue $chainQueue,
      StringConverter $stringConverter,
      Validator $validator
    ) {
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:migrate:data_parser')
            ->setDescription($this->trans('commands.generate.plugin.migrate.data_parser.description'))
            ->setHelp($this->trans('commands.generate.plugin.migrate.data_parser.help'))
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
                $this->trans('commands.generate.plugin.migrate.data_parser.options.class')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.data_parser.options.plugin-id')
            )
            ->addOption(
                'plugin-title',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.data_parser.options.plugin-title')
            )->setAliases(['gpmdp']);
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
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $plugin_id = $input->getOption('plugin-id');
        $plugin_title = $input->getOption('plugin-title');

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'plugin_id' => $plugin_id,
          'plugin_title' => $plugin_title,
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
                $this->trans('commands.generate.plugin.migrate.data_parser.questions.class'),
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
                $this->trans('commands.generate.plugin.migrate.data_parser.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        // 'plugin-title' option.
        $pluginTitle = $input->getOption('plugin-title');
        if (!$pluginTitle) {
          $pluginTitle = $this->getIo()->ask(
            $this->trans('commands.generate.plugin.migrate.data_parser.questions.plugin-title'),
            $this->stringConverter->camelCaseToUnderscore($class)
          );
          $input->setOption('plugin-title', $pluginTitle);
        }
    }
}
