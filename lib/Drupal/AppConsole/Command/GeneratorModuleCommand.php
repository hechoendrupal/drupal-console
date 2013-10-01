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
    protected function configure(){
        $this
            ->setDefinition(array(
                new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
                new InputOption('controller','',InputOption::VALUE_NONE, 'Generate controller'),
                new InputOption('form','',InputOption::VALUE_NONE, 'Generate form'),
                new InputOption('settings','',InputOption::VALUE_NONE, 'Generate settings file'),
                new InputOption('plugin','',InputOption::VALUE_NONE, 'Generate plugin block'),
                new InputOption('services','',InputOption::VALUE_NONE, 'Generate services file'),
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

        if ($input->isInteractive()) {
          if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
            $output->writeln('<error>Command aborted</error>');
            return 1;
          }
        }

        $module = Validators::validateModuleName($input->getOption('module'));
        $structure =  $input->getOption('structure');

        $dir = DRUPAL_ROOT . "/modules";

        $generator = $this->getGenerator();
        $generator->generate($module, $dir, $structure);

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

        if ($module == null ){

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

        $controller = $input->getOption('controller');
        if (!$structure && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate controller', 'yes', '?'), true)) {
          $controller = true;
        }
        $input->setOption('controller', $controller);

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
