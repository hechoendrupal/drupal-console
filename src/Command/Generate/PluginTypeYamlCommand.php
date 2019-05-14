<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeYamlCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeYamlGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginTypeYamlCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginTypeYamlCommand extends Command
{
    use ModuleTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var PluginTypeYamlGenerator
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
     * PluginTypeYamlCommand constructor.
     *
     * @param Manager                 $extensionManager
     * @param PluginTypeYamlGenerator $generator
     * @param StringConverter         $stringConverter
     * @param Validator               $validator
     */
    public function __construct(
        Manager $extensionManager,
        PluginTypeYamlGenerator $generator,
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
            ->setName('generate:plugin:type:yaml')
            ->setDescription($this->trans('commands.generate.plugin.type.yaml.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.yaml.help'))
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
                $this->trans('commands.generate.plugin.type.yaml.options.class')
            )
            ->addOption(
                'plugin-name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-name')
            )
            ->addOption(
                'plugin-file-name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-file-name')
            )
            ->setAliases(['gpty']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $this->validateModule($input->getOption('module'));
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $plugin_name = $input->getOption('plugin-name');
        $plugin_file_name = $input->getOption('plugin-file-name');

        $this->generator->generate([
            'module' => $module,
            'class_name' => $class_name,
            'plugin_name' => $plugin_name,
            'plugin_file_name' => $plugin_file_name,
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
                $this->trans('commands.generate.plugin.type.yaml.options.class'),
                'ExamplePlugin',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class_name);
        }

        // --plugin-name option
        $plugin_name = $input->getOption('plugin-name');
        if (!$plugin_name) {
            $plugin_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-name'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-name', $plugin_name);
        }

        // --plugin-file-name option
        $plugin_file_name = $input->getOption('plugin-file-name');
        if (!$plugin_file_name) {
            $plugin_file_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-file-name'),
                strtr($plugin_name, '_-', '..')
            );
            $input->setOption('plugin-file-name', $plugin_file_name);
        }
    }
}
