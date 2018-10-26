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
                'initialize',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.command.options.initialize')
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
            ->addOption(
                'generator',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.command.options.generator')
            )
            ->setAliases(['gco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extension = $input->getOption('extension');
        $extensionType = $input->getOption('extension-type');
        $class = $this->validator->validateCommandName($input->getOption('class'));
        $name = $input->getOption('name');
        $initialize = $input->getOption('initialize');
        $interact = $input->getOption('interact');
        $containerAware = $input->getOption('container-aware');
        $services = $input->getOption('services');
        $generator = $input->getOption('generator');

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $class_generator = null;
        if ($generator) {
            $class_generator = str_replace('Command', 'Generator', $class);
        }

        $this->generator->generate([
            'extension' => $extension,
            'extension_type' => $extensionType,
            'name' => $name,
            'initialize' => $initialize,
            'interact' => $interact,
            'class_name' => $class,
            'container_aware' => $containerAware,
            'services' => $build_services,
            'class_generator' => $class_generator,
            'generator' => $generator,
        ]);

        $this->site->removeCachedServicesFile();

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $extension = $input->getOption('extension');
        if (!$extension) {
            $extension = $this->extensionQuestion(true, true);
            $input->setOption('extension', $extension->getName());
            $input->setOption('extension-type', $extension->getType());
        }

        $extensionType = $input->getOption('extension-type');
        if (!$extensionType) {
            $extensionType = $this->extensionTypeQuestion();
            $input->setOption('extension-type', $extensionType);
        }

        $name = $input->getOption('name');
        if (!$name) {
            $name = $this->getIo()->ask(
                $this->trans('commands.generate.command.questions.name'),
                sprintf('%s:default', $extension->getName())
            );
            $input->setOption('name', $name);
        }

        $initialize = $input->getOption('initialize');
        if (!$initialize) {
            $initialize = $this->getIo()->confirm(
                $this->trans('commands.generate.command.questions.initialize'),
                false
            );
            $input->setOption('initialize', $initialize);
        }

        $interact = $input->getOption('interact');
        if (!$interact) {
            $interact = $this->getIo()->confirm(
                $this->trans('commands.generate.command.questions.interact'),
                false
            );
            $input->setOption('interact', $interact);
        }

        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
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
            $containerAware = $this->getIo()->confirm(
                $this->trans('commands.generate.command.questions.container-aware'),
                false
            );
            $input->setOption('container-aware', $containerAware);
        }

        if (!$containerAware) {
            // @see use Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion();
            $input->setOption('services', $services);
        }

        $generator = $input->getOption('generator');
        if (!$generator) {
            $generator = $this->getIo()->confirm(
                $this->trans('commands.generate.command.questions.generator'),
                false
            );
            $input->setOption('generator', $generator);
        }
    }
}
