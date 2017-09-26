<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\JsTestCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\JsTestGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Extension\Manager;

class JsTestCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;
    use ContainerAwareCommandTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var JsTestGenerator
     */
    protected $generator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * JsTestCommand constructor.
     *
     * @param Manager         $extensionManager
     * @param JsTestGenerator $generator
     * @param Validator       $validator
     */
    public function __construct(
        Manager $extensionManager,
        JsTestGenerator $generator,
        Validator $validator
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->validator = $validator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:jstest')
            ->setDescription($this->trans('commands.generate.jstest.description'))
            ->setHelp($this->trans('commands.generate.jstest.help'))
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
                $this->trans('commands.generate.jstest.options.class')
            )
            ->setAliases(['gjt']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $yes = $input->hasOption('yes') ? $input->getOption('yes') : false;

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return 1;
        }

        $module = $input->getOption('module');
        $class = $this->validator->validateClassName($input->getOption('class'));

        $this->generator->generate(
            $module,
            $class
        );

        return 0;
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
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.jstest.questions.class'),
                'DefaultJsTest',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }
    }

    /**
     * @return \Drupal\Console\Generator\JsTestGenerator
     */
    protected function createGenerator()
    {
        return new JsTestGenerator();
    }
}
