<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginRestResourceCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\PluginBlockGenerator;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\FormTrait;

class GeneratorPluginRestResourceCommand extends GeneratorCommand
{
  use ServicesTrait;
  use ModuleTrait;
  use FormTrait;

  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('class-name','',InputOption::VALUE_OPTIONAL, 'Plugin Rest Resource class'),
        new InputOption('plugin-label','',InputOption::VALUE_OPTIONAL, 'Plugin Rest Resource Label'),
        new InputOption('plugin-id','',InputOption::VALUE_OPTIONAL, 'Plugin Rest Resource id'),
        new InputOption('plugin-url','',InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Plugin Rest Resource URL'),
      ))
    ->setDescription('Generate plugin rest resource')
    ->setHelp('The <info>generate:plugin:rest:resource</info> command helps you generate a new rest resource.')
    ->setName('generate:plugin:rest:resource');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    if ($input->isInteractive()) {
      if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
        $output->writeln('<error>Command aborted</error>');
        return 1;
      }
    }

    $module = $input->getOption('module');
    $class_name = $input->getOption('class-name');
    $plugin_label = $input->getOption('plugin-label');
    $plugin_id = $input->getOption('plugin-id');
    $services = $input->getOption('services');
    $inputs = $input->getOption('plugin-form');

    // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
    $build_services = $this->buildServices($services);

    $this
      ->getGenerator()
      ->generate($module, $class_name, $plugin_label, $plugin_id, $build_services, $inputs)
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal Plugin Rest Resource generator');

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the plugin rest resource name', 'DefaultRestResource'),
        'DefaultRestResource'
      );
    }
    $input->setOption('class-name', $class_name);

    $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

    // --plugin-label option
    $plugin_id = $input->getOption('plugin-id');
    if (!$plugin_id) {
      $plugin_id = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the plugin rest resource id', $machine_name),
        $machine_name
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --plugin-id option
    $plugin_label = $input->getOption('plugin-label');
    if (!$plugin_label) {
      $plugin_label = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the plugin rest resource label',$machine_name),
        $machine_name
      );
    }
    $input->setOption('plugin-label', $plugin_label);

    // --plugin-url option
    $plugin_url = $input->getOption('plugin-url');
    if (!$plugin_url) {
      $plugin_url = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the plugin rest resource url',$machine_name),
        $machine_name
      );
    }
    $input->setOption('plugin-url', $plugin_url);

    $output->writeln([]);

    $states = $dialog->select(
        $output,
        'You can  select what REST State implement in your resource',
        'GET, PUT, POST, DELETE, PATCH, HEAD, OPTIONS',
        0,
        false,
        'Rest state "%s" is invalid',
        true
    );

    // @see Drupal\AppConsole\Command\Helper\FormTrait::formQuestion
    $form = $this->formQuestion($output, $dialog);
    $input->setOption('plugin-form', $form);
  }

  protected function createGenerator()
  {
    return new PluginRestResourceGenerator();
  }

}
