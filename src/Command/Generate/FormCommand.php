<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\FormCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\MenuTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\Routing\RouteProviderInterface;

abstract class FormCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use FormTrait;
    use MenuTrait;

    private $formType;
    private $commandName;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var FormGenerator
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
     * @var ElementInfoManager
     */
    protected $elementInfoManager;

    /**
     * @var RouteProviderInterface
     */
    protected $routeProvider;


    /**
     * FormCommand constructor.
     *
     * @param Manager                $extensionManager
     * @param FormGenerator          $generator
     * @param ChainQueue             $chainQueue
     * @param StringConverter        $stringConverter
     * @param ElementInfoManager     $elementInfoManager
     * @param RouteProviderInterface $routeProvider
     */
    public function __construct(
        Manager $extensionManager,
        FormGenerator $generator,
        ChainQueue $chainQueue,
        StringConverter $stringConverter,
        ElementInfoManager $elementInfoManager,
        RouteProviderInterface $routeProvider
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        $this->stringConverter = $stringConverter;
        $this->elementInfoManager = $elementInfoManager;
        $this->routeProvider = $routeProvider;
        parent::__construct();
    }

    protected function setFormType($formType)
    {
        return $this->formType = $formType;
    }

    protected function setCommandName($commandName)
    {
        return $this->commandName = $commandName;
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription(
                sprintf(
                    $this->trans('commands.generate.form.description'),
                    $this->formType
                )
            )
            ->setHelp(
                sprintf(
                    $this->trans('commands.generate.form.help'),
                    $this->commandName,
                    $this->formType
                )
            )
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
                $this->trans('commands.generate.form.options.class')
            )
            ->addOption(
                'form-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.form-id')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->addOption(
                'config-file',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.form.options.config-file')
            )
            ->addOption(
                'inputs',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.common.options.inputs')
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.path')
            )
            ->addOption(
                'menu-link-gen',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.menu-link-gen')
            )
            ->addOption(
                'menu-link-title',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.menu-link-title')
            )
            ->addOption(
                'menu-parent',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.menu-parent')
            )
            ->addOption(
                'menu-link-desc',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.menu-link-desc')
            )->setAliases(['gf']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $services = $input->getOption('services');
        $path = $input->getOption('path');
        $config_file = $input->getOption('config-file');
        $class_name = $input->getOption('class');
        $form_id = $input->getOption('form-id');
        $form_type = $this->formType;
        $menu_link_gen = $input->getOption('menu-link-gen');
        $menu_parent = $input->getOption('menu-parent');
        $menu_link_title = $input->getOption('menu-link-title');
        $menu_link_desc = $input->getOption('menu-link-desc');

        // if exist form generate config file
        $inputs = $input->getOption('inputs');
        $build_services = $this->buildServices($services);

        $this
            ->generator
            ->generate($module, $class_name, $form_id, $form_type, $build_services, $config_file, $inputs, $path, $menu_link_gen, $menu_link_title, $menu_parent, $menu_link_desc);

        $this->chainQueue->addCommand('router:rebuild', []);
    }

    /**
     * {@inheritdoc}
     */
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
        $className = $input->getOption('class');
        if (!$className) {
            $className = $io->ask(
                $this->trans('commands.generate.form.questions.class'),
                'DefaultForm'
            );
            $input->setOption('class', $className);
        }

        // --form-id option
        $formId = $input->getOption('form-id');
        if (!$formId) {
            $formId = $io->ask(
                $this->trans('commands.generate.form.questions.form-id'),
                $this->stringConverter->camelCaseToMachineName($className)
            );
            $input->setOption('form-id', $formId);
        }

        // --services option
        // @see use Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
        $services = $this->servicesQuestion($io);
        $input->setOption('services', $services);
        
        // --config_file option
        $config_file = $input->getOption('config-file');

        if (!$config_file) {
            $config_file = $io->confirm(
                $this->trans('commands.generate.form.questions.config-file'),
                true
            );
            $input->setOption('config-file', $config_file);
        }

        // --inputs option
        $inputs = $input->getOption('inputs');
        if (!$inputs) {
            // @see \Drupal\Console\Command\Shared\FormTrait::formQuestion
            $inputs = $this->formQuestion($io);
            $input->setOption('inputs', $inputs);
        }

        $path = $input->getOption('path');
        if (!$path) {
            if ($this->formType == 'ConfigFormBase') {
                $form_path = '/admin/config/{{ module_name }}/{{ class_name_short }}';
                $form_path = sprintf(
                    '/admin/config/%s/%s',
                    $module,
                    strtolower($this->stringConverter->removeSuffix($className))
                );
            } else {
                $form_path = sprintf(
                    '/%s/form/%s',
                    $module,
                    $this->stringConverter->camelCaseToMachineName($this->stringConverter->removeSuffix($className))
                );
            }
            $path = $io->ask(
                $this->trans('commands.generate.form.questions.path'),
                $form_path,
                function ($path) {
                    if (count($this->routeProvider->getRoutesByPattern($path)) > 0) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans(
                                    'commands.generate.form.messages.path-already-added'
                                ),
                                $path
                            )
                        );
                    }

                    return $path;
                }
            );
            $input->setOption('path', $path);
        }

        // --link option for links.menu
        if ($this->formType == 'ConfigFormBase') {
            $menu_options = $this->menuQuestion($io, $className);
            $menu_link_gen = $input->getOption('menu-link-gen');
            $menu_link_title = $input->getOption('menu-link-title');
            $menu_parent = $input->getOption('menu-parent');
            $menu_link_desc = $input->getOption('menu-link-desc');
            if (!$menu_link_gen || !$menu_link_title || !$menu_parent || !$menu_link_desc) {
                $input->setOption('menu-link-gen', $menu_options['menu_link_gen']);
                $input->setOption('menu-link-title', $menu_options['menu_link_title']);
                $input->setOption('menu-parent', $menu_options['menu_parent']);
                $input->setOption('menu-link-desc', $menu_options['menu_link_desc']);
            }
        }
    }
}
