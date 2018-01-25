<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\FormAlterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\FormAlterGenerator;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\webprofiler\Profiler\Profiler;

class FormAlterCommand extends Command
{
    use ConfirmationTrait;
    use ArrayInputTrait;
    use FormTrait;
    use ModuleTrait;
    use ServicesTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var FormAlterGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * @var ElementInfoManager
     */
    protected $elementInfoManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var RouteProviderInterface
     */
    protected $routeProvider;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * FormAlterCommand constructor.
     *
     * @param Manager                $extensionManager
     * @param FormAlterGenerator     $generator
     * @param StringConverter        $stringConverter
     * @param ModuleHandlerInterface $moduleHandler
     * @param ElementInfoManager     $elementInfoManager
     * @param Profiler               $profiler
     * @param $appRoot
     * @param ChainQueue             $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        FormAlterGenerator $generator,
        StringConverter $stringConverter,
        ModuleHandlerInterface $moduleHandler,
        ElementInfoManager $elementInfoManager,
        Profiler $profiler = null,
        $appRoot,
        ChainQueue $chainQueue,
        Validator $validator
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->moduleHandler = $moduleHandler;
        $this->elementInfoManager = $elementInfoManager;
        $this->profiler = $profiler;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        $this->validator = $validator;
        parent::__construct();
    }

    protected $metadata = [
      'class' => [],
      'method'=> [],
      'file'=> [],
      'unset' => []
    ];

    protected function configure()
    {
        $this
            ->setName('generate:form:alter')
            ->setDescription($this->trans('commands.generate.form.alter.description'))
            ->setHelp($this->trans('commands.generate.form.alter.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'form-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.alter.options.form-id')
            )
            ->addOption(
                'inputs',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
            )
            ->setAliases(['gfa']);
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
        $formId = $input->getOption('form-id');
        $inputs = $input->getOption('inputs');
        $noInteraction = $input->getOption('no-interaction');
        // Parse nested data.
        if ($noInteraction) {
          $inputs = $this->explodeInlineArray($inputs);
        }

        $function = $module . '_form_' . $formId . '_alter';

        if ($this->extensionManager->validateModuleFunctionExist($module, $function)) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.form.alter.messages.help-already-implemented'),
                    $module
                )
            );
        }

        $this->generator->generate([
            'module' => $module,
            'form_id' => $formId,
            'inputs' => $inputs,
            'metadata' => $this->metadata,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --form-id option
        $formId = $input->getOption('form-id');
        if (!$formId) {
            $forms = [];
            // Get form ids from webprofiler
            if ($this->moduleHandler->moduleExists('webprofiler')) {
                $this->getIo()->info(
                    $this->trans('commands.generate.form.alter.messages.loading-forms')
                );
                $forms = $this->getWebprofilerForms();
            }

            if (!empty($forms)) {
                $formId = $this->getIo()->choiceNoList(
                    $this->trans('commands.generate.form.alter.questions.form-id'),
                    array_keys($forms)
                );
            }
        }

        if ($this->moduleHandler->moduleExists('webprofiler') && isset($forms[$formId])) {
            $this->metadata['class'] = $forms[$formId]['class']['class'];
            $this->metadata['method'] = $forms[$formId]['class']['method'];
            $this->metadata['file'] = str_replace(
                $this->appRoot,
                '',
                $forms[$formId]['class']['file']
            );

            foreach ($forms[$formId]['form'] as $itemKey => $item) {
                if ($item['#type'] == 'hidden') {
                    unset($forms[$formId]['form'][$itemKey]);
                }
            }

            unset($forms[$formId]['form']['form_build_id']);
            unset($forms[$formId]['form']['form_token']);
            unset($forms[$formId]['form']['form_id']);
            unset($forms[$formId]['form']['actions']);

            $formItems = array_keys($forms[$formId]['form']);

            $formItemsToHide = $this->getIo()->choice(
                $this->trans('commands.generate.form.alter.messages.hide-form-elements'),
                $formItems,
                null,
                true
            );

            $this->metadata['unset'] = array_filter(array_map('trim', $formItemsToHide));
        }

        $input->setOption('form-id', $formId);

        // @see Drupal\Console\Command\Shared\FormTrait::formQuestion
        $inputs = $input->getOption('inputs');

        if (empty($inputs)) {
            $this->getIo()->writeln($this->trans('commands.generate.form.alter.messages.inputs'));
            $inputs = $this->formQuestion();
        } else {
            $inputs= $this->explodeInlineArray($inputs);
        }

        $input->setOption('inputs', $inputs);
    }

    public function getWebprofilerForms()
    {
        $tokens = $this->profiler->find(null, null, 1000, null, '', '');
        $forms = [];
        foreach ($tokens as $token) {
            $token = [$token['token']];
            $profile = $this->profiler->loadProfile($token);
            $formCollector = $profile->getCollector('forms');
            $collectedForms = $formCollector->getForms();
            if (empty($forms)) {
                $forms = $collectedForms;
            } elseif (!empty($collectedForms)) {
                $forms = array_merge($forms, $collectedForms);
            }
        }
        return $forms;
    }
}
