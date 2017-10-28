<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\CacheContextCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\CacheContextGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Core\Utils\StringConverter;

class CacheContextCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ConfirmationTrait;
    use ServicesTrait;

    /**
     * @var CacheContextGenerator
     */
    protected $generator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * CacheContextCommand constructor.
     *
     * @param CacheContextGenerator $generator
     * @param ChainQueue            $chainQueue
     * @param Manager               $extensionManager
     * @param StringConverter       $stringConverter
     * @param Validator             $validator
     */
    public function __construct(
        CacheContextGenerator $generator,
        ChainQueue $chainQueue,
        Manager $extensionManager,
        StringConverter $stringConverter,
        Validator $validator
    ) {
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:cache:context')
            ->setDescription($this->trans('commands.generate.cache.context.description'))
            ->setHelp($this->trans('commands.generate.cache.context.description'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'cache-context',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.cache.context.options.name')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.cache.context.options.class')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )->setAliases(['gcc']);
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
        $cache_context = $input->getOption('cache-context');
        $class = $this->validator->validateClassName($input->getOption('class'));
        $services = $input->getOption('services');

        // @see Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $buildServices = $this->buildServices($services);

        $this->generator->generate($module, $cache_context, $class, $buildServices);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
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

        // --cache_context option
        $cache_context = $input->getOption('cache-context');
        if (!$cache_context) {
            $cache_context = $io->ask(
                $this->trans('commands.generate.cache.context.questions.name'),
                sprintf('%s', $module)
            );
            $input->setOption('cache-context', $cache_context);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.cache.context.questions.class'),
                'DefaultCacheContext',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($io);
            $input->setOption('services', $services);
        }
    }
}
