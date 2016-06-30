<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ModuleFileCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\ModuleFileGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;


class ModuleFileCommand extends GeneratorCommand
{
    use ConfirmationTrait;
    use ModuleTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:module:file')
            ->setDescription($this->trans('commands.generate.module.description'))
            ->setHelp($this->trans('commands.generate.module.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $validators = $this->getValidator();

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }
        

        $machine_name =  $input->getOption('module');
        $module_path =  $this->getSite()->getModulePath($module);

        $generator = $this->getGenerator();
        $generator->generate(
            $machine_name,
            $module_path
        );
    }


    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {

        $io = new DrupalStyle($input, $output);

        $moduleHandler = $this->getModuleHandler();
        $drupal = $this->getDrupalHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
        }
       
        $input->setOption('module', $module);

         // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
        }
       
        $input->setOption('module', $module);
    

    }

    /**
     * @return ModuleFileGenerator
     */
    protected function createGenerator()
    {
        return new ModuleFileGenerator();
    }
}
