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

class GeneratorModuleCommand extends GeneratorCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setDefinition([
      new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
      new InputOption('machine-name','',InputOption::VALUE_REQUIRED, 'The machine name (lowercase and underscore only)'),
      new InputOption('module-path','',InputOption::VALUE_REQUIRED, 'The path of the module'),
      new InputOption('description','',InputOption::VALUE_OPTIONAL, 'Description module'),
      new InputOption('core','',InputOption::VALUE_OPTIONAL, 'Core version'),
      new InputOption('package','',InputOption::VALUE_OPTIONAL, 'Package'),
      new InputOption('controller', '', InputOption::VALUE_NONE, 'Generate controller'),
      new InputOption('tests', '', InputOption::VALUE_NONE, 'Generate tests'),
      new InputOption('setting', '', InputOption::VALUE_NONE, 'Generate settings file'),
      new InputOption('structure', '', InputOption::VALUE_NONE, 'Whether to generate the whole directory structure'),
      new InputOption('skip-root', '', InputOption::VALUE_NONE, 'Generate structure on module existent'),
    ])
    ->setDescription('Generate a module')
    ->setHelp('The <info>generate:module</info> command helps you generates new modules.')
    ->setName('generate:module');
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

    $module = $this->validateModuleName($input->getOption('module'));
    $module_path = $this->validateModulePath($input->getOption('module-path'));
    $description = $input->getOption('description');
    $core = $input->getOption('core');
    $package = $input->getOption('package');
    $controller = $input->getOption('controller');
    $tests = $input->getOption('tests');
    $setting = $input->getOption('setting');
    $structure =  $input->getOption('structure');
    $skip_root =  $input->getOption('skip-root');

    $generator = $this->getGenerator();
    $generator->generate($module, $module_path, $description, $core, $package, $controller, $tests, $setting, $structure, $skip_root);

    $errors = [];

    $dialog->getRunner($output, $errors);

    $dialog->writeGeneratorSummary($output, $errors);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal module generator');

    try {
      $module = $input->getOption('module') ? $this->validateModuleName($input->getOption('module')) : null;
    } catch (\Exception $error) {
      $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
    }

    if (!$module) {
      $module = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion('Module name',''),
        function ($module) {
          return $this->validateModule($module);
        },
        false,
        null,
        null
      );
      $input->setOption('module', $module);
    }

    $drupalBoostrap = $this->getHelperSet()->get('bootstrap');
    $module_path_default = $drupalBoostrap->getDrupalRoot() . "/modules";

    $module_path = $input->getOption('module-path');
    if (!$module_path) {
      $module_path = $dialog->ask($output, $dialog->getQuestion('Module Path', $module_path_default), $module_path_default);
    }
    $input->setOption('module-path', $module_path);

    if (!$input->getOption('skip-root')) {
      $description = $input->getOption('description');
      if (!$description) {
          $description = $dialog->ask($output, $dialog->getQuestion('Description', 'My Awesome Module'), 'My Awesome Module');
      }
      $input->setOption('description', $description);

      $package = $input->getOption('package');
      if (!$package) {
        $package = $dialog->ask($output, $dialog->getQuestion('Package', 'Other'), 'Other');
      }
      $input->setOption('package', $package);
    }

    $core = $input->getOption('core');
    if (!$core) {
      $core = $dialog->ask($output, $dialog->getQuestion('Core', '8.x'), '8.x');
    }
    $input->setOption('core', $core);

    $controller = $input->getOption('controller');
    if (!$controller && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate a Controller', 'no', '?'), false)) {
      $controller = true;
    }
    $input->setOption('controller', $controller);

    $tests = $input->getOption('tests');
    if (!$tests && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate Test', 'yes', '?'), TRUE)) {
      $tests = TRUE;
    }
    $input->setOption('tests', $tests);

    $setting = $input->getOption('setting');
    if (!$setting && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate a setting file', 'no', '?'), false)) {
      $setting = true;
    }
    $input->setOption('setting', $setting);

    $structure = $input->getOption('structure');
    if (!$structure && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate the whole directory structure', 'yes', '?'), true)) {
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
