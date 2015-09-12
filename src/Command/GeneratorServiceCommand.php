<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorServiceCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\ServiceGenerator;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorServiceCommand extends GeneratorCommand
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
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.name')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.class')
            )
            ->addOption(
                'interface',
                null,
                InputOption::VALUE_OPTIONAL,
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
        $dialog = $this->getDialogHelper();

        // @see use Drupal\AppConsole\Command\Helper\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $name = $input->getOption('name');
        $class = $input->getOption('class');
        $interface = $input->getOption('interface');
        $services = $input->getOption('services');

        $interface = ($interface === true || strtolower($interface) === 'yes');

        // @see Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $name, $class, $interface, $build_services);

        $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --service-name option
        $name = $input->getOption('name');
        if (!name) {
            $name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.service.questions.name'),
                    $module.'.default'
                ),
                $module.'.default'
            );
        }
        $input->setOption('name', $name);

        // --class-name option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.service.questions.class'), 'DefaultService'),
                'DefaultService'
            );
        }
        $input->setOption('class', $class);

        // --interface option
        $interface = $input->getOption('interface');
        if (!$interface) {
            $interface = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.service.questions.interface'), 'yes', '?'),
                true
            );
        }
        $input->setOption('interface', $interface);

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\AppConsole\Command\Helper\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($output, $dialog);
        }
        $input->setOption('services', $services);
    }

    protected function createGenerator()
    {
        return new ServiceGenerator();
    }
}
