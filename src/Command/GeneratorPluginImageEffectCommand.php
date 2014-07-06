<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginImageEffectCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\PluginImageEffectGenerator;
use Drupal\AppConsole\Command\Helper\ModuleTrait;

class GeneratorPluginImageEffectCommand extends GeneratorCommand
{
  use ModuleTrait;

  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('class-name','',InputOption::VALUE_REQUIRED, 'Plugin name'),
        new InputOption('plugin-label','',InputOption::VALUE_OPTIONAL, 'Plugin Label'),
        new InputOption('plugin-id','',InputOption::VALUE_OPTIONAL, 'Plugin id'),
        new InputOption('description','',InputOption::VALUE_OPTIONAL, 'Plugin Description'),
      ))
    ->setDescription('Generate image effect plugin')
    ->setHelp('The <info>generate:plugin:imageeffect</info> command helps you generate a new image effect plugin.')
    ->setName('generate:plugin:imageeffect');
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
    $description = $input->getOption('description');

    $this
      ->getGenerator()
      ->generate($module, $class_name, $plugin_label, $plugin_id, $description)
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal Image Effect Plugin generator');

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
        $dialog->getQuestion('Enter the plugin name', 'DefaultImageEffect'),
        'DefaultImageEffect'
      );
    }
    $input->setOption('class-name', $class_name);

    $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

    // --plugin label option
    $plugin_label = $input->getOption('plugin-label');
    if (!$plugin_label) {
      $plugin_label = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the plugin label', $machine_name),
        $machine_name
      );
    }
    $input->setOption('plugin-label', $plugin_label);

    // --name option
    $plugin_id = $input->getOption('plugin-id');

    if (!$plugin_id) {
      $plugin_id = $dialog->ask($output,
        $dialog->getQuestion('Enter the plugin id', $machine_name),
        $machine_name
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --description option
    $description = $input->getOption('description');
    if (!$description) {
      $description = $dialog->ask($output,
        $dialog->getQuestion('Description', 'My Image Effect'),
        'My Image Effect'
      );
    }
    $input->setOption('description', $description);
  }

  protected function createGenerator()
  {
    return new PluginImageEffectGenerator();
  }
}
