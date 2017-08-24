<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginViewsFieldCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginViewsFieldGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginViewsFieldCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginViewsFieldCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginViewsFieldGenerator
*/
    protected $generator;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * PluginViewsFieldCommand constructor.
     *
     * @param Manager                   $extensionManager
     * @param PluginViewsFieldGenerator $generator
     * @param Site                      $site
     * @param StringConverter           $stringConverter
     * @param ChainQueue                $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginViewsFieldGenerator $generator,
        Site $site,
        StringConverter $stringConverter,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->site = $site;
        $this->stringConverter = $stringConverter;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:views:field')
            ->setDescription($this->trans('commands.generate.plugin.views.field.description'))
            ->setHelp($this->trans('commands.generate.plugin.views.field.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.views.field.options.class')
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.views.field.options.title')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.views.field.options.description')
            )
            ->setAliases(['gpvf']);
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
        $class_machine_name = $this->stringConverter->camelCaseToUnderscore($class_name);
        $title = $input->getOption('title');
        $description = $input->getOption('description');

        $this->generator->generate($module, $class_machine_name, $class_name, $title, $description);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
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
                $this->trans('commands.generate.plugin.views.field.questions.class'),
                'CustomViewsField'
            );
        }
        $input->setOption('class', $class_name);

        // --title option
        $title = $input->getOption('title');
        if (!$title) {
            $title = $io->ask(
                $this->trans('commands.generate.plugin.views.field.questions.title'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('title', $title);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.plugin.views.field.questions.description'),
                $this->trans('commands.generate.plugin.views.field.questions.description_default')
            );
            $input->setOption('description', $description);
        }
    }

    protected function createGenerator()
    {
        return new PluginViewsFieldGenerator();
    }
}
