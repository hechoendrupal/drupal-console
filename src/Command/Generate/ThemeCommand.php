<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ThemeCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ThemeRegionTrait;
use Drupal\Console\Command\ThemeBreakpointTrait;
use Drupal\Console\Generator\ThemeGenerator;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 *
 */
class ThemeCommand extends GeneratorCommand
{
    use ConfirmationTrait;
    use ThemeRegionTrait;
    use ThemeBreakpointTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:theme')
            ->setDescription($this->trans('commands.generate.theme.description'))
            ->setHelp($this->trans('commands.generate.theme.help'))
            ->addOption(
                'theme',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.module')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.machine-name')
            )
            ->addOption(
                'theme-path',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.module-path')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.description')
            )
            ->addOption('core', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.theme.options.core'))
            ->addOption(
                'package',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.package')
            )
            ->addOption(
                'global-library',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.global-library')
            )
            ->addOption(
                'base-theme',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.base-theme')
            )
            ->addOption(
                'regions',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.regions')
            )
            ->addOption(
                'breakpoints',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.breakpoints')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $validators = $this->getValidator();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $theme = $validators->validateModuleName($input->getOption('theme'));

        $drupal = $this->getDrupalHelper();
        $drupal_root = $drupal->getRoot();
        $theme_path = $drupal_root . $input->getOption('theme-path');
        $theme_path = $validators->validateModulePath($theme_path, true);

        $machine_name = $validators->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $package = $input->getOption('package');
        $base_theme = $input->getOption('base-theme');
        $global_library = $input->getOption('global-library');
        $regions = $input->getOption('regions');
        $breakpoints = $input->getOption('breakpoints');

        $generator = $this->getGenerator();
        $generator->generate(
            $theme,
            $machine_name,
            $theme_path,
            $description,
            $core,
            $package,
            $base_theme,
            $global_library,
            $regions,
            $breakpoints
        );
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
        $drupalRoot = $drupal->getRoot();

        try {
            $theme = $input->getOption('theme') ? $this->validateModuleName($input->getOption('theme')) : null;
        } catch (\Exception $error) {
            $output->error($error->getMessage());

            return;
        }

        if (!$theme) {
            $theme = $output->ask(
                $this->trans('commands.generate.theme.questions.theme'),
                '',
                function ($theme) use ($validators) {
                    return $validators->validateModuleName($theme);
                }
            );
            $input->setOption('theme', $theme);
        }

        try {
            $machine_name = $input->getOption('machine-name') ? $this->validateModule($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $output->error($error->getMessage());

            return;
        }

        if (!$machine_name) {
            $machine_name = $output->ask(
                $this->trans('commands.generate.module.questions.machine-name'),
                $stringUtils->createMachineName($theme),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machine_name);
        }

        $theme_path = $input->getOption('theme-path');
        if (!$theme_path) {
            $theme_path = $output->ask(
                $this->trans('commands.generate.theme.questions.theme-path'),
                '/themes/custom',
                function ($theme_path) use ($drupalRoot, $machine_name) {
                    $theme_path = ($theme_path[0] != '/' ? '/' : '') . $theme_path;
                    $full_path = $drupalRoot . $theme_path . '/' . $machine_name;
                    if (file_exists($full_path)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.theme.errors.directory-exists'),
                                $full_path
                            )
                        );
                    } else {
                        return $theme_path;
                    }
                }
            );
            $input->setOption('theme-path', $theme_path);
        }

        $description = $input->getOption('description');
        if (!$description) {
            $description = $output->ask(
                $this->trans('commands.generate.theme.questions.description'),
                'My Awesome theme'
            );
            $input->setOption('description', $description);
        }

        $package = $input->getOption('package');
        if (!$package) {
            $package = $output->ask(
                $this->trans('commands.generate.theme.questions.package'),
                'Other'
            );
            $input->setOption('package', $package);
        }

        $core = $input->getOption('core');
        if (!$core) {
            $core = $output->ask(
                $this->trans('commands.generate.theme.questions.core'),
                '8.x'
            );
            $input->setOption('core', $core);
        }

        $base_theme = $input->getOption('base-theme');
        if (!$base_theme) {
            $themeHandler = $this->getThemeHandler();
            $themes = $themeHandler->rebuildThemeData();
            uasort($themes, 'system_sort_modules_by_info_name');

            $base_theme = $output->choiceNoList(
                $this->trans('commands.generate.theme.options.base-theme'),
                array_keys($themes)
            );
            $input->setOption('base-theme', $base_theme);
        }

        $global_library = $input->getOption('global-library');
        if (!$global_library) {
            $global_library = $output->ask(
                $this->trans('commands.generate.theme.questions.global-library'),
                'global-styling'
            );
            $input->setOption('global-library', $global_library);
        }

        // --regions option.
        $regions = $input->getOption('regions');
        if (!$regions) {
            if ($output->confirm(
                $this->trans('commands.generate.theme.questions.regions'),
                true
            )) {
                // @see \Drupal\Console\Command\ThemeRegionTrait::regionQuestion
                $regions = $this->regionQuestion($output);
                $input->setOption('regions', $regions);
            }
        }

        // --breakpoints option.
        $breakpoints = $input->getOption('breakpoints');
        if (!$breakpoints) {
            if ($output->confirm(
                $this->trans('commands.generate.theme.questions.breakpoints'),
                true
            )) {
                // @see \Drupal\Console\Command\ThemeRegionTrait::regionQuestion
                $breakpoints = $this->breakpointQuestion($output);
                $input->setOption('breakpoints', $breakpoints);
            }
        }
    }

    /**
     * @return ThemeGenerator
     */
    protected function createGenerator()
    {
        return new ThemeGenerator();
    }
}
