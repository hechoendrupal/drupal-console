<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\BreakPointCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ThemeRegionTrait;
use Drupal\Console\Command\Shared\ThemeBreakpointTrait;
use Drupal\Console\Generator\BreakPointGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 *
 */
class BreakPointCommand extends GeneratorCommand
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
            ->setName('generate:breakpoint')
            ->setDescription($this->trans('commands.generate.breakpoint.description'))
            ->setHelp($this->trans('commands.generate.breakpoint.help'))
            ->addOption(
                'theme',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.breakpoint.options.theme')
            )
            ->addOption(
                'breakpoints',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.breakpoint.options.breakpoints')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }
        
        $validators = $this->getValidator();
        // we must to ensure theme exist
        $machine_name = $validators->validateMachineName($input->getOption('theme'));
        $theme_path = $drupal_root . $input->getOption('theme');
        $breakpoints = $input->getOption('breakpoints');
        
        $generator = $this->getGenerator();

        $generator->generate(
            $theme_path,
            $breakpoints,
            $machine_name
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $stringUtils = $this->getStringHelper();
        $drupal = $this->getDrupalHelper();
        $drupalRoot = $drupal->getRoot();
        
        // --base-theme option.
        $base_theme = $input->getOption('theme');

        if (!$base_theme) {
            $themeHandler = $this->getThemeHandler();
            $themes = $themeHandler->rebuildThemeData();
            $themes['classy'] ='';

            uasort($themes, 'system_sort_modules_by_info_name');

            $base_theme = $io->choiceNoList(
                $this->trans('commands.generate.breakpoint.questions.theme'),
                array_keys($themes)
            );
            $input->setOption('theme', $base_theme);
        }
         
        // --breakpoints option.
        $breakpoints = $input->getOption('breakpoints');
        if (!$breakpoints) {
            $breakpoints = $this->breakpointQuestion($output);
            $input->setOption('breakpoints', $breakpoints);
        }
    }

    /**
     * @return BreakPointGenerator
     */
    protected function createGenerator()
    {
        return new  BreakPointGenerator();
    }
}
