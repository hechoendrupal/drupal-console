<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ModuleCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\ModuleGenerator;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class ModuleCommand extends GeneratorCommand
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
                'feature',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.module.options.feature')
            )
            ->addOption(
                'composer',
                false,
                InputOption::VALUE_NONE,
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
        $output = new DrupalStyle($input, $output);

        $validators = $this->getValidator();
        $messageHelper = $this->getMessageHelper();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $validators->validateModuleName($input->getOption('module'));

        $drupal = $this->getDrupalHelper();
        $drupalRoot = $drupal->getRoot();
        $modulePath = $drupalRoot.$input->getOption('module-path');
        $modulePath = $validators->validateModulePath($modulePath, true);

        $machineName = $validators->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $package = $input->getOption('package');
        $feature = $input->getOption('feature');
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
            $machineName,
            $modulePath,
            $description,
            $core,
            $package,
            $feature,
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
        $localModules = array();

        $modules = system_rebuild_module_data();
        foreach ($modules as $module_id => $module) {
            array_push($localModules, basename($module->subpath));
        }

        $checkDependencies = [
          'local_modules' => [],
          'drupal_modules' => [],
          'no_modules' => [],
        ];

        foreach ($dependencies as $module) {
            if (in_array($module, $localModules)) {
                $checkDependencies['local_modules'][] = $module;
            } else {
                $response = $client->head('https://www.drupal.org/project/'.$module);
                $header_link = explode(';', $response->getHeader('link'));
                if (empty($header_link[0])) {
                    $checkDependencies['no_modules'][] = $module;
                } else {
                    $checkDependencies['drupal_modules'][] = $module;
                }
            }
        }

        return $checkDependencies;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();
        $drupal = $this->getDrupalHelper();

        try {
            $module = $input->getOption('module') ?
              $this->validateModuleName(
                  $input->getOption('module')
              ) : null;
        } catch (\Exception $error) {
            $output->error($error->getMessage());

            return;
        }

        if (!$module) {
            $module = $output->ask(
                $this->trans('commands.generate.module.questions.module'),
                null,
                function ($module) use ($validators) {
                    return $validators->validateModuleName($module);
                }
            );
            $input->setOption('module', $module);
        }

        try {
            $machineName = $input->getOption('machine-name') ?
              $this->validateModule(
                  $input->getOption('machine-name')
              ) : null;
        } catch (\Exception $error) {
            $output->error($error->getMessage());
        }

        if (!$machineName) {
            $machineName = $output->ask(
                $this->trans('commands.generate.module.questions.machine-name'),
                $stringUtils->createMachineName($module),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machineName);
        }

        $modulePath = $input->getOption('module-path');
        if (!$modulePath) {
            $drupalRoot = $drupal->getRoot();
            $modulePath = $output->ask(
                $this->trans('commands.generate.module.questions.module-path'),
                '/modules/custom',
                function ($modulePath) use ($drupalRoot, $machineName) {
                    $modulePath = ($modulePath[0] != '/' ? '/' : '').$modulePath;
                    $fullPath = $drupalRoot.$modulePath.'/'.$machineName;
                    if (file_exists($fullPath)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.module.errors.directory-exists'),
                                $fullPath
                            )
                        );
                    }

                    return $modulePath;
                }
            );
        }
        $input->setOption('module-path', $modulePath);

        $description = $input->getOption('description');
        if (!$description) {
            $description = $output->ask(
                $this->trans('commands.generate.module.questions.description'),
                'My Awesome Module'
            );
        }
        $input->setOption('description', $description);

        $package = $input->getOption('package');
        if (!$package) {
            $package = $output->ask(
                $this->trans('commands.generate.module.questions.package'),
                'Other'
            );
        }
        $input->setOption('package', $package);

        $core = $input->getOption('core');
        if (!$core) {
            $core = $output->ask(
                $this->trans('commands.generate.module.questions.core'), '8.x',
                '8.x'
            );
        }
        $input->setOption('core', $core);

        $feature = $input->getOption('feature');
        if (!$feature) {
            $feature = $output->confirm(
                $this->trans('commands.generate.module.questions.feature'),
                false
            );
        }
        $input->setOption('feature', $feature);

        $composer = $input->getOption('composer');
        if (!$composer) {
            $composer = $output->confirm(
                $this->trans('commands.generate.module.questions.composer'),
                true
            );
        }
        $input->setOption('composer', $composer);

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            $addDependencies = $output->confirm(
                $this->trans('commands.generate.module.questions.dependencies'),
                false
            );
            if ($addDependencies) {
                $dependencies = $output->ask(
                    $this->trans('commands.generate.module.options.dependencies')
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
