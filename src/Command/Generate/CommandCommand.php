<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\CommandCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ExtensionTrait;
use Drupal\Console\Command\Shared\ServicesTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\CommandGenerator;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Utils\Site;

class CommandCommand extends ContainerAwareCommand

{
    use ConfirmationTrait;
    use ServicesTrait;
    use ModuleTrait;
    use ExtensionTrait;

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
     * @var Site
     */
    protected $site;

    /**
     * CommandCommand constructor.
     *
     * @param CommandGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     * @param Site             $site
     */
    public function __construct(
        CommandGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        Site  $site
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->site = $site;
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
                'extension',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.extension')
            )
            ->addOption(
                'extension-type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.extension-type')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.command.options.class')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.command.options.name')
            )
            ->addOption(
                'interact',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.command.options.interact')
            )
            ->addOption(
                'container-aware',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.command.options.container-aware')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->setAliases(['gco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $extension = $input->getOption('extension');
        $extensionType = $input->getOption('extension-type');
        $class = $input->getOption('class');
        $name = $input->getOption('name');
        $interact = $input->getOption('interact');
        $containerAware = $input->getOption('container-aware');
        $services = $input->getOption('services');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return 1;
        }

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this->generator->generate(
            $extension,
            $extensionType,
            $name,
            $interact,
            $class,
            $containerAware,
            $build_services
        );

        $this->site->removeCachedServicesFile();

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $extension = $input->getOption('extension');
        if (!$extension) {
            $extension = $this->extensionQuestion($io, true, true);
            $input->setOption('extension', $extension->getName());
            $input->setOption('extension-type', $extension->getType());
        }

        $extensionType = $input->getOption('extension-type');
        if (!$extensionType) {
            $extensionType = $this->extensionTypeQuestion($io);
            $input->setOption('extension-type', $extensionType);
        }

        $name = $input->getOption('name');
        if (!$name) {
            $name = $io->ask(
                $this->trans('commands.generate.command.questions.name'),
                sprintf('%s:default', $extension->getName())
            );
            $input->setOption('name', $name);
        }

        $interact = $input->getOption('interact');

        if (!$interact) {
            $interact = $io->confirm(
                $this->trans('commands.generate.command.questions.interact'),
                true
            );
            $input->setOption('interact', $interact);
        }

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

        $containerAware = $input->getOption('container-aware');
        if (!$containerAware) {
            $containerAware = $io->confirm(
                $this->trans('commands.generate.command.questions.container-aware'),
                false
            );
            $input->setOption('container-aware', $containerAware);
        }

        if (!$containerAware) {
            // @see use Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($io);
            $input->setOption('services', $services);
        }
    }
}
