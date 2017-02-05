<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginMigrateProcessCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Generator\PluginMigrateProcessGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ChainQueue;

class PluginMigrateProcessCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;
    use ContainerAwareCommandTrait;

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
     * PluginBlockCommand constructor.
     *
     * @param PluginMigrateProcessGenerator $generator
     * @param ChainQueue                    $chainQueue
     * @param Manager                       $extensionManager
     * @param StringConverter               $stringConverter
     */
    public function __construct(
        PluginMigrateProcessGenerator $generator,
        ChainQueue $chainQueue,
        Manager $extensionManager,
        StringConverter $stringConverter
    ) {
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:migrate:process')
            ->setDescription($this->trans('commands.generate.plugin.migrate.process.description'))
            ->setHelp($this->trans('commands.generate.plugin.migrate.process.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.process.options.class')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.migrate.process.options.plugin-id')
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
            return 1;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $plugin_id = $input->getOption('plugin-id');

        $this->generator->generate($module, $class_name, $plugin_id);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // 'module-name' option.
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // 'class-name' option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.plugin.migrate.process.questions.class'),
                ucfirst($this->stringConverter->underscoreToCamelCase($module))
            );
            $input->setOption('class', $class);
        }

        // 'plugin-id' option.
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $io->ask(
                $this->trans('commands.generate.plugin.migrate.source.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }
    }
}
