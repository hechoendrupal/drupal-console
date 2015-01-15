<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\GeneratorModuleCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\ModuleGenerator;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorModuleCommand extends GeneratorCommand
{
  use ConfirmationTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setDefinition([
      new InputOption('module','',InputOption::VALUE_REQUIRED, $this->trans('command.generate.module.options.module')),
      new InputOption('machine-name','',InputOption::VALUE_REQUIRED, $this->trans('command.generate.module.options.machine-name')),
      new InputOption('module-path','',InputOption::VALUE_REQUIRED, $this->trans('command.generate.module.options.module-path')),
      new InputOption('description','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.module.options.description')),
      new InputOption('core','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.module.options.core')),
      new InputOption('package','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.module.options.package')),
      new InputOption('controller', '', InputOption::VALUE_NONE, $this->trans('command.generate.module.options.controller')),
      new InputOption('test', '', InputOption::VALUE_NONE, $this->trans('command.generate.module.options.test')),
      new InputOption('structure', '', InputOption::VALUE_NONE, $this->trans('command.generate.module.options.structure')),
    ])
    ->setDescription($this->trans('command.generate.module.description'))
    ->setHelp($this->trans('command.generate.module.help'))
    ->setName('generate:module');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $validators = $this->getHelperSet()->get('validators');

    if ($this->confirmationQuestion($input, $output, $dialog)) {
      return;
    }

    $module = $validators->validateModuleName($input->getOption('module'));
    $module_path = $validators->validateModulePath($input->getOption('module-path'), true);
    $machine_name = $validators->validateMachineName($input->getOption('machine-name'));
    $description = $input->getOption('description');
    $core = $input->getOption('core');
    $package = $input->getOption('package');
    $controller = $input->getOption('controller');
    $test = $input->getOption('test');
    $structure =  $input->getOption('structure');

    $generator = $this->getGenerator();
    $generator->generate(
            $module,
            $machine_name,
            $module_path,
            $description,
            $core,
            $package,
            $controller,
            $test,
            $structure
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $stringUtils = $this->getHelperSet()->get('stringUtils');
    $validators = $this->getHelperSet()->get('validators');
    $dialog = $this->getDialogHelper();

    try {
      $module = $input->getOption('module') ? $this->validateModuleName($input->getOption('module')) : null;
    } catch (\Exception $error) {
      $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
    }

    $module = $input->getOption('module');
    if (!$module) {
      $module = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('command.generate.module.questions.module'), ''),
        function ($module) use ($validators){
          return $validators->validateModuleName($module);
        },
        false,
        null,
        null
      );
    }
    $input->setOption('module', $module);
    
    try {
        $machine_name = $input->getOption('machine-name') ? $this->validateModule($input->getOption('machine-name')) : null;
    } catch (\Exception $error) {
        $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
    }

    if (!$machine_name) {
      $machine_name = $stringUtils->createMachineName($module);
      $machine_name = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('command.generate.module.questions.machine-name'), $machine_name),
        function ($machine_name) use ($validators){
          return $validators->validateMachineName($machine_name);
        },
        false,
        $machine_name,
        null
      );
      $input->setOption('machine-name', $machine_name);
    }

    $drupalBoostrap = $this->getHelperSet()->get('bootstrap');
    $module_path_default = $drupalBoostrap->getDrupalRoot() . "/modules/custom";

    $module_path = $input->getOption('module-path');
    if (!$module_path) {
      $module_path = $dialog->ask($output, $dialog->getQuestion($this->trans('command.generate.module.questions.module-path'), $module_path_default), $module_path_default);
    }
    $input->setOption('module-path', $module_path);

    $description = $input->getOption('description');
    if (!$description) {
      $description = $dialog->ask($output, $dialog->getQuestion($this->trans('command.generate.module.questions.description'), 'My Awesome Module'), 'My Awesome Module');
    }
    $input->setOption('description', $description);

    $package = $input->getOption('package');
    if (!$package) {
      $package = $dialog->ask($output, $dialog->getQuestion($this->trans('command.generate.module.questions.package'), 'Other'), 'Other');
    }
    $input->setOption('package', $package);

    $core = $input->getOption('core');
    if (!$core) {
      $core = $dialog->ask($output, $dialog->getQuestion($this->trans('command.generate.module.questions.core'), '8.x'), '8.x');
    }
    $input->setOption('core', $core);

    $controller = $input->getOption('controller');
    if (!$controller && $dialog->askConfirmation($output, $dialog->getQuestion($this->trans('command.generate.module.questions.controller'), 'no', '?'), false)) {
      $controller = true;
    }
    $input->setOption('controller', $controller);

    if ($controller){
      $test = $input->getOption('test');
      if (!$test && $dialog->askConfirmation($output, $dialog->getQuestion($this->trans('command.generate.module.questions.test'), 'yes', '?'), true)) {
        $test = true;
      }
    }
    else {
      $test = false;
    }
    $input->setOption('test', $test);

    $structure = $input->getOption('structure');
    if (!$structure && $dialog->askConfirmation($output, $dialog->getQuestion($this->trans('command.generate.module.questions.structure'), 'yes', '?'), true)) {
      $structure = true;
    }
    $input->setOption('structure', $structure);
  }

  /**
  * @return ModuleGenerator
  */
  protected function createGenerator()
  {
    return new ModuleGenerator();
  }
}
