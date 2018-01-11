<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ControllerCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\AjaxCommandGenerator;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;

class AjaxCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var ControllerGenerator
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

    protected function configure()
    {
        $this
            ->setName('generate:ajax:command')
            ->setDescription($this->trans('commands.generate.controller.description'))
            ->setHelp($this->trans('commands.generate.controller.help'))
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
                $this->trans('commands.generate.controller.options.class')
            )
            ->addOption(
                'method',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.controller.options.class')
            )
            ->setAliases(['gac']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $input)) {
            return 1;
        }

        $module = $input->getOption('module');
        $class = $this->validator->validateClassName($input->getOption('class'));
        $method = $input->getOption('method');

        $this->generator->generate(
            $module,
            $class,
            $method
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
        $io = new DrupalStyle($input, $output);

        // --module option
        $this->getModuleOption();

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.controller.questions.class'),
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
            $method = $io->ask(
                $this->trans('commands.generate.controller.questions.method'),
                'hello'
            );
            $input->setOption('method', $method);
        }
    }

    /**
     * @return \Drupal\Console\Generator\AjaxCommandGenerator
     */
    protected function createGenerator()
    {
        return new AjaxCommandGenerator();
    }
}
