<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ServiceCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Generator\ServiceGenerator;
use Drupal\Console\Command\ConfirmationTrait;
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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $name = $input->getOption('name');
        $class = $input->getOption('class');
        $interface = $input->getOption('interface');
        $services = $input->getOption('services');

        // @see Drupal\Console\Command\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $name, $class, $interface, $build_services);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $output->ask(
                $this->trans('commands.generate.service.questions.service-name'),
                $module.'.default'
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $output->ask(
                $this->trans('commands.generate.service.questions.class'),
                'DefaultService'
            );
            $input->setOption('class', $class);
        }

        // --interface option
        $interface = $input->getOption('interface');
        if (!$interface) {
            $interface = $output->confirm(
                $this->trans('commands.generate.service.questions.interface'),
                true
            );
            $input->setOption('interface', $interface);
        }

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($output);
            $input->setOption('services', $services);
        }
    }

    protected function createGenerator()
    {
        return new ServiceGenerator();
    }
}
