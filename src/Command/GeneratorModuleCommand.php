<?php

/**
 * @file
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
        $this
          ->setName('generate:module')
          ->setDescription($this->trans('commands.generate.module.description'))
          ->setHelp($this->trans('commands.generate.module.help'))
          ->addOption(
              'module',
              '',
              InputOption::VALUE_REQUIRED,
              $this->trans('commands.generate.module.options.module')
          )
          ->addOption(
              'machine-name',
              '',
              InputOption::VALUE_REQUIRED,
              $this->trans('commands.generate.module.options.machine-name')
          )
          ->addOption(
              'module-path',
              '',
              InputOption::VALUE_REQUIRED,
              $this->trans('commands.generate.module.options.module-path')
          )
          ->addOption(
              'description',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.module.options.description')
          )
          ->addOption(
              'core',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.module.options.core')
          )
          ->addOption(
              'package',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.module.options.package')
          )
          ->addOption(
              'composer',
              false,
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.module.options.composer')
          )
          ->addOption(
              'dependencies',
              false,
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.module.options.dependencies')
          );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $validators = $this->getHelperSet()->get('validators');
        $messageHelper = $this->getHelperSet()->get('message');

        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $validators->validateModuleName($input->getOption('module'));

        $drupalAutoLoad = $this->getHelperSet()->get('drupal-autoload');
        $drupal_root = $drupalAutoLoad->getDrupalRoot();
        $module_path = $drupal_root.$input->getOption('module-path');
        $module_path = $validators->validateModulePath($module_path, true);

        $machine_name = $validators->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $package = $input->getOption('package');
        $composer = $input->getOption('composer');
        /*
         * Modules Dependencies
         */
        $dependencies = $validators->validateModuleDependencies($input->getOption('dependencies'));
        // Check if all module dependencies are available
        if ($dependencies) {
            $checked_dependencies = $this->checkDependencies($dependencies['success']);
            if (!empty($checked_dependencies['no_modules'])) {
                $messageHelper->addWarningMessage(
                    sprintf(
                        $this->trans('commands.generate.module.warnings.module-unavailable'),
                        implode(', ', $checked_dependencies['no_modules'])
                    )
                );
            }
            $dependencies = $dependencies['success'];
        }

        $generator = $this->getGenerator();
        $generator->generate(
            $module,
            $machine_name,
            $module_path,
            $description,
            $core,
            $package,
            $composer,
            $dependencies
        );
    }


    /**
     * @param  array $dependencies
     * @return array
     */
    private function checkDependencies(array $dependencies)
    {
        $client = $this->getHttpClient();
        $local_modules = array();

        $modules = system_rebuild_module_data();
        foreach ($modules as $module_id => $module) {
            array_push($local_modules, basename($module->subpath));
        }

        $checked_dependecies = array(
          'local_modules' => array(),
          'drupal_modules' => array(),
          'no_modules' => array(),
        );

        foreach ($dependencies as $module) {
            if (in_array($module, $local_modules)) {
                $checked_dependecies['local_modules'][] = $module;
            } else {
                $response = $client->head('https://www.drupal.org/project/'.$module);
                $header_link = explode(';', $response->getHeader('link'));
                if (empty($header_link[0])) {
                    $checked_dependecies['no_modules'][] = $module;
                } else {
                    $checked_dependecies['drupal_modules'][] = $module;
                }
            }
        }

        return $checked_dependecies;
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
                $dialog->getQuestion($this->trans('commands.generate.module.questions.module'), ''),
                function ($module) use ($validators) {
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
                $dialog->getQuestion($this->trans('commands.generate.module.questions.machine-name'), $machine_name),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                },
                false,
                $machine_name,
                null
            );
            $input->setOption('machine-name', $machine_name);
        }

        $module_path = $input->getOption('module-path');
        $drupalAutoLoad = $this->getHelperSet()->get('drupal-autoload');
        $drupal_root = $drupalAutoLoad->getDrupalRoot();

        if (!$module_path) {
            $module_path_default = '/modules/custom';

            $module_path = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.module.questions.module-path'),
                    $module_path_default
                ),
                function ($module_path) use ($drupal_root, $machine_name) {
                    $module_path = ($module_path[0] != '/' ? '/' : '').$module_path;
                    $full_path = $drupal_root.$module_path.'/'.$machine_name;
                    if (file_exists($full_path)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.module.errors.directory-exists'),
                                $full_path
                            )
                        );
                    } else {
                        return $module_path;
                    }
                },
                false,
                $module_path_default,
                null
            );
        }
        $input->setOption('module-path', $module_path);

        $description = $input->getOption('description');
        if (!$description) {
            $description = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.module.questions.description'), 'My Awesome Module'),
                'My Awesome Module'
            );
        }
        $input->setOption('description', $description);

        $package = $input->getOption('package');
        if (!$package) {
            $package = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.module.questions.package'), 'Other'),
                'Other'
            );
        }
        $input->setOption('package', $package);

        $core = $input->getOption('core');
        if (!$core) {
            $core = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.module.questions.core'), '8.x'),
                '8.x'
            );
        }
        $input->setOption('core', $core);

        $composer = $input->getOption('composer');
        if (!$composer && $dialog->askConfirmation(
            $output,
            $dialog->getQuestion($this->trans('commands.generate.module.questions.composer'), 'no', '?'),
            false
        )
        ) {
            $composer = true;
        }
        $input->setOption('composer', $composer);

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            if ($dialog->askConfirmation(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.module.questions.dependencies'), 'no', '?'),
                false
            )
            ) {
                $dependencies = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.generate.module.options.dependencies'), ''),
                    function ($dependencies) {
                        return $dependencies;
                    },
                    false,
                    null,
                    null
                );
            }
        }
        $input->setOption('dependencies', $dependencies);
    }

    /**
     * @return ModuleGenerator
     */
    protected function createGenerator()
    {
        return new ModuleGenerator();
    }
}
