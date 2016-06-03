<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ProfileCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Generator\ProfileGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

class ProfileCommand extends GeneratorCommand
{
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:profile')
            ->setDescription($this->trans('commands.generate.profile.description'))
            ->setHelp($this->trans('commands.generate.profile.help'))
            ->addOption(
                'profile',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.profile')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.machine-name')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.description')
            )
            ->addOption(
                'core',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.core')
            )
            ->addOption(
                'dependencies',
                false,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.dependencies')
            )
            ->addOption(
                'distribution',
                false,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.distribution')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $validators = $this->getValidator();

        if (!$this->confirmGeneration($io)) {
            return;
        }

        $profile = $validators->validateModuleName($input->getOption('profile'));
        $machine_name = $validators->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $distribution = $input->getOption('distribution');
        $profile_path = $this->getDrupalHelper()->getRoot() . '/profiles';

        // Check if all module dependencies are available.
        $dependencies = $validators->validateModuleDependencies($input->getOption('dependencies'));
        if ($dependencies) {
            $checked_dependencies = $this->checkDependencies($dependencies['success']);
            if (!empty($checked_dependencies['no_modules'])) {
                $io->info(
                    sprintf(
                        $this->trans('commands.generate.profile.warnings.module-unavailable'),
                        implode(', ', $checked_dependencies['no_modules'])
                    )
                );
            }
            $dependencies = $dependencies['success'];
        }

        $generator = $this->getGenerator();
        $generator->generate(
            $profile,
            $machine_name,
            $profile_path,
            $description,
            $core,
            $dependencies,
            $distribution
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
        $local_modules = array();

        $modules = system_rebuild_module_data();
        foreach ($modules as $module_id => $module) {
            array_push($local_modules, basename($module->subpath));
        }

        $checked_dependencies = array(
            'local_modules' => array(),
            'drupal_modules' => array(),
            'no_modules' => array(),
        );

        foreach ($dependencies as $module) {
            if (in_array($module, $local_modules)) {
                $checked_dependencies['local_modules'][] = $module;
            } else {
                $response = $client->head('https://www.drupal.org/project/' . $module);
                $header_link = explode(';', $response->getHeader('link'));
                if (empty($header_link[0])) {
                    $checked_dependencies['no_modules'][] = $module;
                } else {
                    $checked_dependencies['drupal_modules'][] = $module;
                }
            }
        }

        return $checked_dependencies;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();

        try {
            // A profile is technically also a module, so we can use the same
            // validator to check the name.
            $profile = $input->getOption('profile') ? $this->validateModuleName($input->getOption('profile')) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return;
        }

        if (!$profile) {
            $profile = $io->ask(
                $this->trans('commands.generate.profile.questions.profile'),
                '',
                function ($profile) use ($validators) {
                    return $validators->validateModuleName($profile);
                }
            );
            $input->setOption('profile', $profile);
        }

        try {
            $machine_name = $input->getOption('machine-name') ? $this->validateModule($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return;
        }

        if (!$machine_name) {
            $machine_name = $io->ask(
                $this->trans('commands.generate.profile.questions.machine-name'),
                $stringUtils->createMachineName($profile),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machine_name);
        }

        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.profile.questions.description'),
                'My Useful Profile'
            );
            $input->setOption('description', $description);
        }

        $core = $input->getOption('core');
        if (!$core) {
            $core = $io->ask(
                $this->trans('commands.generate.profile.questions.core'),
                '8.x'
            );
            $input->setOption('core', $core);
        }

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            if ($io->confirm(
                $this->trans('commands.generate.profile.questions.dependencies'),
                true
            )) {
                $dependencies = $output->ask(
                    $this->trans('commands.generate.profile.options.dependencies'),
                    ''
                );
            }
            $input->setOption('dependencies', $dependencies);
        }

        $distribution = $input->getOption('distribution');
        if (!$distribution) {
            if ($io->confirm(
                $this->trans('commands.generate.profile.questions.distribution'),
                false
            )) {
                $distribution = $output->ask(
                    $this->trans('commands.generate.profile.options.distribution'),
                    'My Kick-ass Distribution'
                );
                $input->setOption('distribution', $distribution);
            }
        }
    }

    /**
     * @return ProfileGenerator
     */
    protected function createGenerator()
    {
        return new ProfileGenerator();
    }
}
