<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ServiceCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\ServiceGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class ServiceCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class ServiceCommand extends ContainerAwareCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var ServiceGenerator
*/
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * ServiceCommand constructor.
     *
     * @param Manager          $extensionManager
     * @param ServiceGenerator $generator
     * @param StringConverter  $stringConverter
     * @param ChainQueue       $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        ServiceGenerator $generator,
        StringConverter $stringConverter,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:service')
            ->setDescription($this->trans('commands.generate.service.description'))
            ->setHelp($this->trans('commands.generate.service.description'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.service.options.service-name')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.service.options.class')
            )
            ->addOption(
                'interface',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.service.options.interface')
            )
            ->addOption(
                'interface-name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.interface-name')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->addOption(
                'path-service',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.path')
            )
            ->setAliases(['gs']);
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
        $name = $input->getOption('name');
        $class = $input->getOption('class');
        $interface = $input->getOption('interface');
        $interface_name = $input->getOption('interface-name');
        $services = $input->getOption('services');
        $path_service = $input->getOption('path-service');

        $available_services = $this->container->getServiceIds();

        if (in_array($name, array_values($available_services))) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.service.messages.service-already-taken'),
                    $module
                )
            );
        }

        // @see Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);
        $this->generator->generate($module, $name, $class, $interface, $interface_name, $build_services, $path_service);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

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

        //--name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $io->ask(
                $this->trans('commands.generate.service.questions.service-name'),
                $module.'.default'
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.service.questions.class'),
                'DefaultService'
            );
            $input->setOption('class', $class);
        }

        // --interface option
        $interface = $input->getOption('interface');
        if (!$interface) {
            $interface = $io->confirm(
                $this->trans('commands.generate.service.questions.interface'),
                true
            );
            $input->setOption('interface', $interface);
        }

        // --interface_name option
        $interface_name = $input->getOption('interface-name');
        if ($interface && !$interface_name) {
            $interface_name = $io->askEmpty(
                $this->trans('commands.generate.service.questions.interface-name')
            );
            $input->setOption('interface-name', $interface_name);
        }

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($io);
            $input->setOption('services', $services);
        }

        // --path_service option
        $path_service = $input->getOption('path-service');
        if (!$path_service) {
            $path_service = $io->ask(
                $this->trans('commands.generate.service.questions.path'),
                '/modules/custom/' . $module . '/src/'
            );
            $input->setOption('path-service', $path_service);
        }
    }
}
