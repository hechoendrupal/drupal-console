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
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class ServiceCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use ConfirmationTrait;

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
                $this->trans('commands.generate.service.options.name')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.service.options.class')
            )
            ->addOption(
                'interface',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.common.service.options.interface')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->addOption(
                'path_service',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.path')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $name = $input->getOption('name');
        $class = $input->getOption('class');
        $interface = $input->getOption('interface');
        $services = $input->getOption('services');
        $path_service = $input->getOption('path_service');


        // @see Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);
        $this
            ->getGenerator()
            ->generate($module, $name, $class, $interface, $build_services, $path_service);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
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
            $module = $this->moduleQuestion($output);
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

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($output);
            $input->setOption('services', $services);
        }

        // --path_service option
        $path_service = $input->getOption('path_service');
        if (!$path_service) {
            $path_service = $io->ask(
                $this->trans('commands.generate.service.questions.path'),
                '/modules/custom/' . $module . '/src/'
            );
            $input->setOption('path_service', $path_service);
        }
    }

    protected function createGenerator()
    {
        return new ServiceGenerator();
    }
}
