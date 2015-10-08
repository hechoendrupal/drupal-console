<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorServiceCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Generator\EventSubscriberGenerator;
use Drupal\Console\Command\ConfirmationTrait;

class GeneratorEventSubscriberCommand extends GeneratorCommand
{
    use EventsTrait;
    use ServicesTrait;
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:event:subscriber')
            ->setDescription($this->trans('commands.generate.event.subscriber.description'))
            ->setHelp($this->trans('commands.generate.event.subscriber.description'))
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
                'events',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
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

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $name = $input->getOption('name');
        $class = $input->getOption('class');
        $events = $input->getOption('events');
        $services = $input->getOption('services');

        // @see Drupal\Console\Command\ServicesTrait::buildServices
        $buildServices = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $name, $class, $events, $buildServices);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
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
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --service-name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.service.questions.service-name'),
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
                $dialog->getQuestion($this->trans('commands.generate.event.subscriber.questions.class-name'), ucfirst($module) .  'DefaultSubscriber'),
                ucfirst($module) .  'DefaultSubscriber'
            );
        }
        $input->setOption('class', $class);

        // --events option
        $events = $input->getOption('events');
        if (!$events) {
            // @see Drupal\Console\Command\ServicesTrait::servicesQuestion
            $events = $this->eventsQuestion($output, $dialog);
        }
        $input->setOption('events', $events);

        // --services option
        $services = $input->getOption('services');
        if (!$services) {
            // @see Drupal\Console\Command\ServicesTrait::servicesQuestion
            $services = $this->servicesQuestion($output, $dialog);
        }
        $input->setOption('services', $services);
    }

    protected function createGenerator()
    {
        return new EventSubscriberGenerator();
    }
}
