<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorModuleCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ThemeRegionTrait;
use Drupal\AppConsole\Generator\ThemeGenerator;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorThemeCommand extends GeneratorCommand
{
    use ConfirmationTrait;
    use ThemeRegionTrait;

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
          );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $validators = $this->getHelperSet()->get('validators');

        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $theme = $validators->validateModuleName($input->getOption('theme'));

        $drupalAutoLoad = $this->getHelperSet()->get('drupal-autoload');
        $drupal_root = $drupalAutoLoad->getDrupalRoot();
        $theme_path = $drupal_root.$input->getOption('theme-path');
        $theme_path = $validators->validateModulePath($theme_path, true);

        $machine_name = $validators->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $package = $input->getOption('package');
        $base_theme = $input->getOption('base-theme');
        $global_library = $input->getOption('global-library');
        $regions = $input->getOption('regions');

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
            $regions
        );
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
            $theme = $input->getOption('theme') ? $this->validateModuleName($input->getOption('theme')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        $theme = $input->getOption('theme');
        if (!$theme) {
            $theme = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.questions.theme'), ''),
                function ($module) use ($validators) {
                    return $validators->validateModuleName($module);
                },
                false,
                null,
                null
            );
        }
        $input->setOption('theme', $theme);

        try {
            $machine_name = $input->getOption('machine-name') ? $this->validateModule($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (!$machine_name) {
            $machine_name = $stringUtils->createMachineName($theme);
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

        $theme_path = $input->getOption('theme-path');
        $drupalAutoLoad = $this->getHelperSet()->get('drupal-autoload');
        $drupal_root = $drupalAutoLoad->getDrupalRoot();

        if (!$theme_path) {
            $theme_path_default = '/themes/custom';

            $theme_path = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.theme.questions.theme-path'),
                    $theme_path_default
                ),
                function ($theme_path) use ($drupal_root, $machine_name) {
                    $theme_path = ($theme_path[0] != '/' ? '/' : '').$theme_path;
                    $full_path = $drupal_root.$theme_path.'/'.$machine_name;
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
                },
                false,
                $theme_path_default,
                null
            );
        }
        $input->setOption('theme-path', $theme_path);

        $description = $input->getOption('description');
        if (!$description) {
            $description = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.questions.description'), 'My Awesome Theme'),
                'My Awesome Module'
            );
        }
        $input->setOption('description', $description);

        $package = $input->getOption('package');
        if (!$package) {
            $package = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.questions.package'), 'Other'),
                'Other'
            );
        }
        $input->setOption('package', $package);

        $core = $input->getOption('core');
        if (!$core) {
            $core = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.questions.core'), '8.x'),
                '8.x'
            );
        }
        $input->setOption('core', $core);

        $themeHandler = $this->getThemeHandler();

        $themes = $themeHandler->rebuildThemeData();
        uasort($themes, 'system_sort_modules_by_info_name');

        $base_theme = $input->getOption('base-theme');
        if (!$base_theme) {
            $base_theme = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.options.base-theme'), ''),
                function ($base_theme) use ($themes) {
                    if ($base_theme == '' || isset($themes[$base_theme])) {
                        return $base_theme;
                    } else {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.theme.questions.invalid-theme'), $base_theme)
                        );
                    }
                },
                false,
                null,
                array_keys($themes)
            );
        }
        $input->setOption('base-theme', $base_theme);

        $global_library = $input->getOption('global-library');
        if (!$global_library) {
            $global_library = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.theme.questions.global-library'), 'global-styling'),
              'global-styling'
            );
        }
        $input->setOption('global-library', $global_library);

        // --regions option
        $regions = $input->getOption('regions');
        if (!$regions) {
            if ($dialog->askConfirmation(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.theme.questions.regions'), 'no', '?'),
              false
            )
            ) {
                // @see \Drupal\AppConsole\Command\Helper\ThemeRegionTrait::regionQuestion
                $regions = $this->regionQuestion($output, $dialog);
            }
        }
        $input->setOption('regions', $regions);

    }

    /**
     * @return ThemeGenerator
     */
    protected function createGenerator()
    {
        return new ThemeGenerator();
    }
}
