<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ServiceCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\ServiceGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
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
     * @var Validator
     */
    protected $validator;

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
     * @param Validator        $validator
     * @param ChainQueue       $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        ServiceGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
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
                'logger-channel',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.service.options.logger-channel')
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
                $this->trans('commands.generate.service.options.path-service')
            )
            ->setAliases(['gs']);
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
        $name = $input->getOption('name');
        $class = $this->validator->validateClassName($input->getOption('class'));
        $interface = $input->getOption('interface');
        $interface_name = $input->getOption('interface-name');
        $logger_channel = $input->getOption('logger-channel');
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
        $this->generator->generate([
            'module' => $module,
            'name' => $name,
            'class' => $class,
            'interface' => $interface,
            'interface_name' => $interface_name,
            'logger_channel' => $logger_channel,
            'services' => $build_services,
            'path_service' => $path_service,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $module = $this->getModuleOption();

        //--name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $this->getIo()->ask(
                $this->trans('commands.generate.service.questions.service-name'),
                $module.'.default'
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.service.questions.class'),
                'DefaultService',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --interface option
        $interface = $input->getOption('interface');
        if (!$interface) {
            $interface = $this->getIo()->confirm(
                $this->trans('commands.generate.service.questions.interface'),
                true
            );
            $input->setOption('interface', $interface);
        }

        // --interface_name option
        $interface_name = $input->getOption('interface-name');
        if ($interface && !$interface_name) {
            $interface_name = $this->getIo()->askEmpty(
                $this->trans('commands.generate.service.questions.interface-name')
            );
            $input->setOption('interface-name', $interface_name);
        }

        // --logger-channel option
        $logger_channel = $input->getOption('logger-channel');
        if (!$logger_channel) {
          $logger_channel = $this->getIo()->confirm(
            $this->trans('commands.generate.service.questions.logger-channel'),
            true
          );
          $input->setOption('logger-channel', $logger_channel);
        }

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion();
            $input->setOption('services', $services);
        }

        // --path_service option
        $path_service = $input->getOption('path-service');
        if (!$path_service) {
            $path_service = $this->getIo()->ask(
                $this->trans('commands.generate.service.questions.path-service'),
                '/modules/custom/' . $module . '/src/'
            );
            $input->setOption('path-service', $path_service);
        }
    }
}
