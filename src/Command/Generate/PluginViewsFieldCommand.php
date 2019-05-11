<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginViewsFieldCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Command\Shared\ArrayInputTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Generator\PluginViewsFieldGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PluginViewsFieldCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginViewsFieldCommand extends Command
{
    use ArrayInputTrait;
    use ConfirmationTrait;
    use ModuleTrait;

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
     * @var Validator
     */
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * PluginViewsFieldCommand constructor.
     *
     * @param Manager $extensionManager
     * @param PluginViewsFieldGenerator $generator
     * @param Site $site
     * @param StringConverter $stringConverter
     * @param Validator $validator
     * @param ChainQueue $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginViewsFieldGenerator $generator,
        Site $site,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->site = $site;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
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
                'fields',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.views.field.options.fields')
            )
            ->setAliases(['gpvf']);
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
        $fields = $input->getOption('fields');
        $noInteraction = $input->getOption('no-interaction');

        // Parse nested data.
        if ($noInteraction) {
            $fields = $this->explodeInlineArray($fields);
        }

        $function = $module . '_views_data';
        $viewsFile = $module . '.views.inc';
        if ($this->extensionManager->validateModuleFunctionExist($module, $function, $viewsFile)) {
            $this->getIo()->warning(
                sprintf(
                    $this->trans('commands.generate.plugin.views.field.messages.views-data-already-implemented'),
                    $module
                )
            );
        }

        $this->generator->generate([
            'module' => $module,
            'fields' => $fields,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --fields option
        $fields = $input->getOption('fields');
        if (empty($fields)) {
            while (true) {
                // --class option
                $class_name = $this->getIo()->ask(
                    $this->trans('commands.generate.plugin.views.field.questions.class'),
                    'CustomViewsField',
                    function ($class_name) {
                        return $this->validator->validateClassName($class_name);
                    }
                );

                // --title option
                $title = $this->getIo()->ask(
                    $this->trans('commands.generate.plugin.views.field.questions.title'),
                    $this->stringConverter->camelCaseToHuman($class_name)
                );

                // --description option
                $description = $this->getIo()->ask(
                    $this->trans('commands.generate.plugin.views.field.questions.description'),
                    $this->trans('commands.generate.plugin.views.field.questions.description_default')
                );

                array_push(
                    $fields,
                    [
                        'title' => $title,
                        'description' => $description,
                        'class_name' => $class_name,
                        'class_machine_name' => $this->stringConverter->camelCaseToUnderscore($class_name),
                    ]
                );

                if (!$this->getIo()->confirm(
                    $this->trans('commands.generate.plugin.views.field.questions.field-add'),
                    true
                )
                ) {
                    break;
                }
            }
        } else {
            $fields = $this->explodeInlineArray($fields);
        }
        $input->setOption('fields', $fields);
    }
}
