<?php

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\GeneratorCommand;
use Drupal\AppConsole\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Generator\ModuleGenerator;

class GeneratorModuleCommand extends GeneratorCommand {

    /**
     * Set the command options
     */
    protected function configure() {
        $this
            ->setDefinition(array(
                new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
                new InputOption('description','',InputOption::VALUE_OPTIONAL, 'Description module'),
                new InputOption('package','',InputOption::VALUE_OPTIONAL, 'Package'),
                new InputOption('routing', '', InputOption::VALUE_NONE, 'Generate routing file'),
                new InputOption('structure', '', InputOption::VALUE_NONE, 'Whether to generate the whole directory structure'),
            ))
            ->setDescription('Generate a module')
            ->setHelp('The <info>generate:module</info> command helps you generates new modules.')
            ->setName('generate:module');
    }

    /**
     *
     * @param  InputInterface  $input  [description]
     * @param  OutputInterface $output [description]
     * @return [type]                  [description]
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $dialog = $this->getDialogHelper();
        $dir = DRUPAL_ROOT . "/modules";

        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
                $output->writeln('<error>Command aborted</error>');
                return 1;
            }
        }

        $module = Validators::validateModuleName($input->getOption('module'));

        $description = $input->getOption('description');
        $package = $input->getOption('package');
        $routing = $input->getOption('routing');
        $structure =  $input->getOption('structure');

        $generator = $this->getGenerator();
        $generator->generate($module, $dir, $description, $package, $routing, $structure);

        $dialog->writeGeneratorSummary($output, $errors);
      }

    /**
     * [interact description]
     * @param  InputInterface  $input  [description]
     * @param  OutputInterface $output [description]
     * @return [type]                  [description]
     */
    protected function interact(InputInterface $input, OutputInterface $output) {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Drupal module generator');

        /**
         * module interactive option
         */
        $module = null;

        try {
          $namespace = $input->getOption('module') ? Validators::validateModuleName($input->getOption('module')) : null;
        }catch (\Exception $error){
          $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        /**
         * Module name
         * @var
         */
        if ($module == null ) {
            $module = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion('Module name',
                $input->getOption('module')),
                array(
                    'Drupal\AppConsole\Command\Validators',
                    'validateModuleName'
                ),
                false,
                $input->getOption('module')
            );

            $input->setOption('module', $module);
        }

        /**
         * Module description
         * @var
         */
        $description = $input->getOption('description');
        if (!$description) {
            $description = $dialog->ask($output, $dialog->getQuestion('Description', 'My Awesome Module'), 'My Awesome Module');
        }
        $input->setOption('description', $description);

        /**
         * Module package
         * @var
         */
        $package = $input->getOption('package');
        if (!$package) {
            $package = $dialog->ask($output, $dialog->getQuestion('Package', 'Other'), 'Other');
        }
        $input->setOption('package', $package);

        /**
         * Generate routing
         * @var [type]
         */
        $routing = $input->getOption('routing');
        if (!$routing && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate a routing file', 'yes', '?'), true)) {
            $routing = true;
        }
        $input->setOption('routing', $routing);

        /**
         * Generate Structure
         * @var boolean
         */
        $structure = $input->getOption('structure');
        if (!$structure && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate the whole directory structure', 'no', '?'), false)) {
            $structure = true;
        }
        $input->setOption('structure', $structure);

    }

    /**
    * Get a filesystem
    * @return [type] Drupal Filesystem
    */
    protected function createGenerator() {
        return new ModuleGenerator();
    }
}
