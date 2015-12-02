<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ProfileCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Generator\ProfileGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $dialog = $this->getDialogHelper();
        $validators = $this->getValidator();
        $messageHelper = $this->getMessageHelper();

        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $profile = $validators->validateModuleName($input->getOption('profile'));
        $machine_name = $validators->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $distribution = $input->getOption('distribution');

        // Check if all module dependencies are available.
        $dependencies = $validators->validateModuleDependencies($input->getOption('dependencies'));
        if ($dependencies) {
            $checked_dependencies = $this->checkDependencies($dependencies['success']);
            if (!empty($checked_dependencies['no_modules'])) {
                $messageHelper->addWarningMessage(
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
        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();
        $dialog = $this->getDialogHelper();

        try {
            // A profile is technically also a module, so we can use the same
            // validator to check the name.
            $input->getOption('profile') ? $this->validateModuleName($input->getOption('profile')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getFormatterHelper()->formatBlock($error->getMessage(), 'error'));
        }

        $profile = $input->getOption('profile');
        if (!$profile) {
            $profile = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.profile.questions.profile'), ''),
                function ($profile) use ($validators) {
                    return $validators->validateModuleName($profile);
                },
                false,
                null,
                null
            );
        }
        $input->setOption('profile', $profile);

        try {
            $machine_name = $input->getOption('machine-name') ? $this->validateModule($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getFormatterHelper()->formatBlock($error->getMessage(), 'error'));
        }

        if (!$machine_name) {
            $machine_name = $stringUtils->createMachineName($profile);
            $machine_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.profile.questions.machine-name'), $machine_name),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                },
                false,
                $machine_name,
                null
            );
            $input->setOption('machine-name', $machine_name);
        }

        $description = $input->getOption('description');
        if (!$description) {
            $description = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.profile.questions.description'), 'My Useful Profile'),
                'My Useful Profile'
            );
        }
        $input->setOption('description', $description);

        $core = $input->getOption('core');
        if (!$core) {
            $core = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.profile.questions.core'), '8.x'),
                '8.x'
            );
        }
        $input->setOption('core', $core);

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            if ($dialog->askConfirmation(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.profile.questions.dependencies'), 'no', '?'),
                false
            )) {
                $dependencies = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.generate.profile.options.dependencies'), ''),
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

        $distribution = $input->getOption('distribution');
        if (!$distribution) {
            if ($dialog->askConfirmation(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.profile.questions.distribution'), 'no', '?'),
                false
            )) {
                $distribution = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.generate.profile.options.distribution'), 'My Kick-ass Distribution'),
                    function ($distribution) {
                        return $distribution;
                    },
                    false,
                    null,
                    null
                );
            }
        }
        $input->setOption('distribution', $distribution);
    }

    /**
     * @return ProfileGenerator
     */
    protected function createGenerator()
    {
        return new ProfileGenerator();
    }

}
