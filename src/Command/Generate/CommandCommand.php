<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\CommandCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ServicesTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\CommandGenerator;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\Validator;

class CommandCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConfirmationTrait;
    use ServicesTrait;
    use ModuleTrait;

    /**
     * @var CommandGenerator
     */
    protected $generator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * CommandCommand constructor.
     * @param CommandGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     */
    public function __construct(
        CommandGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:command')
            ->setDescription($this->trans('commands.generate.command.description'))
            ->setHelp($this->trans('commands.generate.command.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.command.options.class')
            )
            ->addOption(
                'name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.command.options.name')
            )
            ->addOption(
                'container-aware',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.command.options.container-aware')
            )
            ->addOption(
                'services',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getOption('module');
        $class = $input->getOption('class');
        $name = $input->getOption('name');
        $containerAware = $input->getOption('container-aware');
        $services = $input->getOption('services');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this->generator->generate(
            $module,
            $name,
            $class,
            $containerAware,
            $build_services
        );
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
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --name
        $name = $input->getOption('name');
        if (!$name) {
            $name = $io->ask(
                $this->trans('commands.generate.command.questions.name'),
                sprintf('%s:default', $module)
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.command.questions.class'),
                'DefaultCommand',
                function ($class) {
                    return $this->validator->validateCommandName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --container-aware option
        $containerAware = $input->getOption('container-aware');
        if (!$containerAware) {
            $containerAware = $io->confirm(
                $this->trans('commands.generate.command.questions.container-aware'),
                false
            );
            $input->setOption('container-aware', $containerAware);
        }

        if (!$containerAware) {
            // --services option
            // @see use Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($io);
            $input->setOption('services', $services);
        }
    }
}
