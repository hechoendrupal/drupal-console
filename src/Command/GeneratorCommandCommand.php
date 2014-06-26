<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorCommandCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\CommandGenerator;

class GeneratorCommandCommand extends GeneratorCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module.'),
        new InputOption('name','',InputOption::VALUE_OPTIONAL, 'Commmand Name'),
        new InputOption('command','',InputOption::VALUE_OPTIONAL, 'Commmand Name'),
        new InputOption('container', '', InputOption::VALUE_NONE, 'Get access to the services container'),
      ))
    ->setDescription('Generate commands for the console')
    ->setHelp('The <info>generate:command</info> command helps you generate a new command.')
    ->setName('generate:command');
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
    $command = $input->getOption('command');
    $name = $input->getOption('name');
    $container = $input->getOption('container');

    $this
      ->getGenerator()
      ->generate($module, $command, $name, $container)
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal Command generator');

    $helper_set = $this->getHelperSet()->get('dialog');

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // Module names
      $modules = $this->getModules();
      $module = $helper_set->askAndValidate(
        $output,
        $dialog->getQuestion('Enter your module',''),
        function ($module) {
          return $this->validateModuleExist($module);
        },
        false,
        '',
        $modules
      );
    }
    $input->setOption('module', $module);

    // --command
    $command = $input->getOption('command');
    if (!$command) {
      $command = $dialog->ask($output,
      $dialog->getQuestion('Enter the command name', $module.':default'), $module.':default');
      $input->setOption('command', $command);
    }
    $input->setOption('command', $command);

    // --name option
    $name = $input->getOption('name');
    if (!$name) {
      $name = $dialog->ask($output,
      $dialog->getQuestion('Enter the class command name', 'DefaultCommand'), 'DefaultCommand');
      $input->setOption('name', $name);
    }
    $input->setOption('name', $name);

    // --container option
    $container = $input->getOption('container');
    if (!$container && $dialog->askConfirmation($output,
    $dialog->getQuestion('Access to services container', 'yes', '?'), TRUE)) {
      $container = TRUE;
    }
    $input->setOption('container', $container);
  }

  protected function createGenerator()
  {
    return new CommandGenerator();
  }
}
