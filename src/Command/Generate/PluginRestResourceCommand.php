<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRestResourceCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Generator\PluginRestResourceGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class PluginRestResourceCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginRestResourceCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginRestResourceGenerator
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
     * PluginRestResourceCommand constructor.
     *
     * @param Manager                     $extensionManager
     * @param PluginRestResourceGenerator $generator
     * @param StringConverter             $stringConverter
     * @param ChainQueue                  $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginRestResourceGenerator $generator,
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
            ->setName('generate:plugin:rest:resource')
            ->setDescription($this->trans('commands.generate.plugin.rest.resource.description'))
            ->setHelp($this->trans('commands.generate.plugin.rest.resource.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rest.resource.options.class')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.name')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-id')
            )
            ->addOption(
                'plugin-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-label')
            )
            ->addOption(
                'plugin-url',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-url')
            )
            ->addOption(
                'plugin-states',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-states')
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
        $plugin_id = $input->getOption('plugin-id');
        $plugin_label = $input->getOption('plugin-label');
        $plugin_url = $input->getOption('plugin-url');
        $plugin_states = $input->getOption('plugin-states');

        $this->generator->generate($module, $class_name, $plugin_label, $plugin_id, $plugin_url, $plugin_states);

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
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $stringUtils = $this->stringConverter;
            $class_name = $io->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.class'),
                'DefaultRestResource',
                function ($class_name) use ($stringUtils) {
                    if (!strlen(trim($class_name))) {
                        throw new \Exception('The Class name can not be empty');
                    }

                    return $stringUtils->humanToCamelCase($class_name);
                }
            );
            $input->setOption('class', $class_name);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --plugin-label option
        $plugin_label = $input->getOption('plugin-label');
        if (!$plugin_label) {
            $plugin_label = $io->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('plugin-label', $plugin_label);
        }

        // --plugin-url option
        $plugin_url = $input->getOption('plugin-url');
        if (!$plugin_url) {
            $plugin_url = $io->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-url')
            );
            $input->setOption('plugin-url', $plugin_url);
        }

        // --plugin-states option
        $plugin_states = $input->getOption('plugin-states');
        if (!$plugin_states) {
            $states = ['GET', 'PUT', 'POST', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
            $plugin_states = $io->choice(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-states'),
                $states,
                null,
                true
            );

            $input->setOption('plugin-states', $plugin_states);
        }
    }
}
