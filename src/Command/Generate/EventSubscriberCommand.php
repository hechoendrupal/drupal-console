<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EventSubscriberCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\EventSubscriberGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\EventsTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Console\Core\Utils\ChainQueue;

class EventSubscriberCommand extends ContainerAwareCommand
{
    use EventsTrait;
    use ServicesTrait;
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var EventSubscriberGenerator
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * EventSubscriberCommand constructor.
     *
     * @param Manager                  $extensionManager
     * @param EventSubscriberGenerator $generator
     * @param StringConverter          $stringConverter
     * @param Validator                $validator
     * @param EventDispatcherInterface $eventDispatcher
     * @param ChainQueue               $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        EventSubscriberGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        EventDispatcherInterface $eventDispatcher,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:event:subscriber')
            ->setDescription($this->trans('commands.generate.event.subscriber.description'))
            ->setHelp($this->trans('commands.generate.event.subscriber.description'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.event.subscriber.options.name')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.event.subscriber.options.class')
            )
            ->addOption(
                'events',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.events')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->setAliases(['ges']);
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
        $name = $input->getOption('name');
        $class = $this->validator->validateClassName($input->getOption('class'));
        $events = $input->getOption('events');
        $services = $input->getOption('services');

        // @see Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $buildServices = $this->buildServices($services);

        $this->generator->generate([
            'module' => $module,
            'name' => $name,
            'class' => $class,
            'events' => $events,
            'services' => $buildServices,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $module = $this->getModuleOption();

        // --service-name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $this->getIo()->ask(
                $this->trans('commands.generate.service.questions.service-name'),
                sprintf('%s.default', $module)
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.event.subscriber.questions.class'),
                'DefaultSubscriber',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --events option
        $events = $input->getOption('events');
        if (!$events) {
            // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $events = $this->eventsQuestion();
            $input->setOption('events', $events);
        }

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion();
            $input->setOption('services', $services);
        }
    }
}
