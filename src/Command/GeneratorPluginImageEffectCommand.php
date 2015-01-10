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
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPluginImageEffectCommand extends GeneratorCommand
{
  use ModuleTrait;
  use ConfirmationTrait;

  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, $this->trans('common.options.module')),
        new InputOption('class-name','',InputOption::VALUE_REQUIRED, $this->trans('command.generate.plugin.imageeffect.options.class-name')),
        new InputOption('label','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.plugin.imageeffect.options.label')),
        new InputOption('plugin-id','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.plugin.imageeffect.options.plugin-id')),
        new InputOption('description','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.plugin.imageeffect.options.description')),
      ))
    ->setDescription($this->trans('command.generate.plugin.imageeffect.description'))
    ->setHelp($this->trans('command.generate.plugin.imageeffect.help'))
    ->setName('generate:plugin:imageeffect');
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
    $class_name = $input->getOption('class-name');
    $label = $input->getOption('label');
    $plugin_id = $input->getOption('plugin-id');
    $description = $input->getOption('description');

    $this
      ->getGenerator()
      ->generate($module, $class_name, $label, $plugin_id, $description)
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, $this->trans('command.generate.plugin.imageeffect.welcome'));

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
        $dialog->getQuestion($this->trans('command.generate.plugin.imageeffect.questions.class-name'), 'DefaultImageEffect'),
        'DefaultImageEffect'
      );
    }
    $input->setOption('class-name', $class_name);

    $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

    // --plugin label option
    $label = $input->getOption('label');
    if (!$label) {
      $label = $dialog->ask(
        $output,
        $dialog->getQuestion( $this->trans('command.generate.plugin.imageeffect.questions.label'), $machine_name),
        $machine_name
      );
    }
    $input->setOption('label', $label);

    // --name option
    $plugin_id = $input->getOption('plugin-id');

    if (!$plugin_id) {
      $plugin_id = $dialog->ask($output,
        $dialog->getQuestion($this->trans('command.generate.plugin.imageeffect.questions.plugin-id'), $machine_name),
        $machine_name
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --description option
    $description = $input->getOption('description');
    if (!$description) {
      $description = $dialog->ask($output,
        $dialog->getQuestion($this->trans('command.generate.plugin.imageeffect.questions.description'), 'My Image Effect'),
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
