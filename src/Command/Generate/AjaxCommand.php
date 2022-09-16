<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ControllerCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\AjaxCommandGenerator;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;

/**
 * Class AjaxCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class AjaxCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var AjaxCommandGenerator
     */
    protected $generator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * AjaxCommand constructor.
     *
     * @param Manager              $extensionManager
     * @param AjaxCommandGenerator $generator
     * @param Validator            $validator
     * @param ChainQueue           $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        AjaxCommandGenerator $generator,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:ajax:command')
            ->setDescription($this->trans('commands.generate.ajax.command.description'))
            ->setHelp($this->trans('commands.generate.ajax.command.help'))
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
                $this->trans('commands.generate.ajax.command.options.class')
            )
            ->addOption(
                'method',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.ajax.command.options.method')
            )
            ->addOption(
                'js-name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.ajax.command.options.js-name')
            )
            ->setAliases(['gac']);
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
        $class = $this->validator->validateClassName($input->getOption('class'));
        $method = $input->getOption('method');
        $js_name = $input->getOption('js-name');

        $this->generator->generate(
            [
                'module' => $module,
                'class_name' => $class,
                'method' => $method,
                'js_name' => $js_name,
            ]
        );

        // Run cache rebuild to see changes in Web UI
        $this->chainQueue->addCommand('router:rebuild', []);

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.ajax.command.questions.class'),
                'AjaxCommand',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --method option
        $method = $input->getOption('method');
        if (!$method) {
            $method = $this->getIo()->ask(
                $this->trans('commands.generate.ajax.command.questions.method'),
                'hello'
            );
            $input->setOption('method', $method);
        }

        // --js-name option
        $js_name = $input->getOption('js-name');
        if (!$js_name) {
            $js_name = $this->getIo()->ask(
                $this->trans('commands.generate.ajax.command.questions.js-name'),
                'script'
            );
            $input->setOption('js-name', $js_name);
        }
    }
}
