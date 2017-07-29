<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeYamlCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeYamlGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class PluginTypeYamlCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginTypeYamlCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

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
     * PluginTypeYamlCommand constructor.
     *
     * @param Manager                 $extensionManager
     * @param PluginTypeYamlGenerator $generator
     * @param StringConverter         $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        PluginTypeYamlGenerator $generator,
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
            ->setName('generate:plugin:type:yaml')
            ->setDescription($this->trans('commands.generate.plugin.type.yaml.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.yaml.help'))
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
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
        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $plugin_name = $input->getOption('plugin-name');
        $plugin_file_name = $input->getOption('plugin-file-name');

        $this->generator->generate($module, $class_name, $plugin_name, $plugin_file_name);
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
                $this->trans('commands.generate.plugin.type.yaml.options.class'),
                'ExamplePlugin'
            );
            $input->setOption('class', $class_name);
        }

        // --plugin-name option
        $plugin_name = $input->getOption('plugin-name');
        if (!$plugin_name) {
            $plugin_name = $io->ask(
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-name'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-name', $plugin_name);
        }

        // --plugin-file-name option
        $plugin_file_name = $input->getOption('plugin-file-name');
        if (!$plugin_file_name) {
            $plugin_file_name = $io->ask(
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-file-name'),
                strtr($plugin_name, '_-', '..')
            );
            $input->setOption('plugin-file-name', $plugin_file_name);
        }
    }
}
