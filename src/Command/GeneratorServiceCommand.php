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
      ->addOption('module',null,InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
      ->addOption('service-name',null,InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.service.options.service-name'))
      ->addOption('class-name',null,InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.service.options.class-name'))
      ->addOption('services',null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $this->trans('commands.common.options.services'))
    ;
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
    $service_name = $input->getOption('service-name');
    $class_name = $input->getOption('class-name');
    $services = $input->getOption('services');

    // @see Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
    $build_services = $this->buildServices($services);

    $this
      ->getGenerator()
      ->generate($module, $service_name, $class_name, $build_services);
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
    $service_name = $input->getOption('service-name');
    if (!$service_name) {
      $service_name = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.service.questions.service-name'), $module.'.default'),
        $module.'.default'
      );
    }
    $input->setOption('service-name', $service_name);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.service.questions.class-name'), 'DefaultService'),
        'DefaultService'
      );
    }
    $input->setOption('class-name', $class_name);

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
