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
use Drupal\Console\Command\Shared\ConfirmationTrait;
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
                'module-file',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.module.options.module-file')
            )
            ->addOption(
                'features-bundle',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.features-bundle')
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.module.options.composer')
            )
            ->addOption(
                'dependencies',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.dependencies')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        $validators = $this->getValidator();

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
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
        $moduleFile = $input->getOption('module-file');
        $featuresBundle = $input->getOption('features-bundle');
        $composer = $input->getOption('composer');

         // Modules Dependencies, re-factor and share with other commands
        $dependencies = $validators->validateModuleDependencies($input->getOption('dependencies'));
        // Check if all module dependencies are available
        if ($dependencies) {
            $checked_dependencies = $this->checkDependencies($dependencies['success']);
            if (!empty($checked_dependencies['no_modules'])) {
                $io->warning(
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
            $moduleFile,
            $featuresBundle,
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
        $this->getDrupalHelper()->loadLegacyFile('/core/modules/system/system.module');
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
        $io = new DrupalStyle($input, $output);

        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();
        $drupal = $this->getDrupalHelper();

        try {
            $module = $input->getOption('module') ?
              $this->validateModuleName(
                  $input->getOption('module')
              ) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return;
        }

        if (!$module) {
            $module = $io->ask(
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
            $io->error($error->getMessage());
        }

        if (!$machineName) {
            $machineName = $io->ask(
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
            $modulePath = $io->ask(
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
            $description = $io->ask(
                $this->trans('commands.generate.module.questions.description'),
                'My Awesome Module'
            );
        }
        $input->setOption('description', $description);

        $package = $input->getOption('package');
        if (!$package) {
            $package = $io->ask(
                $this->trans('commands.generate.module.questions.package'),
                'Custom'
            );
        }
        $input->setOption('package', $package);

        $core = $input->getOption('core');
        if (!$core) {
            $core = $io->ask(
                $this->trans('commands.generate.module.questions.core'), '8.x',
                function ($core) {
                    // Only allow 8.x and higher as core version.
                    if (!preg_match('/^([0-9]+)\.x$/', $core, $matches) || ($matches[1] < 8)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.module.errors.invalid-core'),
                                $core
                            )
                        );
                    }

                    return $core;
                }
            );
            $input->setOption('core', $core);
        }

        $moduleFile = $input->getOption('module-file');
        if (!$moduleFile) {
            $moduleFile = $io->confirm(
                $this->trans('commands.generate.module.questions.module-file'),
                true
            );
            $input->setOption('module-file', $moduleFile);
        }

        $featuresBundle = $input->getOption('features-bundle');
        if (!$featuresBundle) {
            $featuresSupport = $io->confirm(
                $this->trans('commands.generate.module.questions.features-support'),
                false
            );
            if ($featuresSupport) {
                $featuresBundle = $io->ask(
                    $this->trans('commands.generate.module.questions.features-bundle'),
                    'default'
                );
            }
            $input->setOption('features-bundle', $featuresBundle);
        }

        $composer = $input->getOption('composer');
        if (!$composer) {
            $composer = $io->confirm(
                $this->trans('commands.generate.module.questions.composer'),
                true
            );
            $input->setOption('composer', $composer);
        }

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            $addDependencies = $io->confirm(
                $this->trans('commands.generate.module.questions.dependencies'),
                false
            );
            if ($addDependencies) {
                $dependencies = $io->ask(
                    $this->trans('commands.generate.module.options.dependencies')
                );
            }
            $input->setOption('dependencies', $dependencies);
        }
    }

    /**
     * @return ModuleGenerator
     */
    protected function createGenerator()
    {
        return new ModuleGenerator();
    }
}
