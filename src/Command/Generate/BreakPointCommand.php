<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\BreakPointCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ThemeBreakpointTrait;
use Drupal\Console\Command\Shared\ThemeRegionTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Generator\BreakPointGenerator;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ThemeHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BreakPointCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class BreakPointCommand extends Command
{
    use ConfirmationTrait;
    use ThemeRegionTrait;
    use ThemeBreakpointTrait;

    /**
     * @var BreakPointGenerator
     */
    protected $generator;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * BreakPointCommand constructor.
     *
     * @param BreakPointGenerator $generator
     * @param string              $appRoot
     * @param ThemeHandler        $themeHandler
     * @param Validator           $validator
     * @param StringConverter     $stringConverter
     */
    public function __construct(
        BreakPointGenerator $generator,
        $appRoot,
        ThemeHandler $themeHandler,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->generator = $generator;
        $this->appRoot = $appRoot;
        $this->themeHandler = $themeHandler;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

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
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.breakpoint.options.theme')
            )
            ->addOption(
                'breakpoints',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.breakpoint.options.breakpoints')
            )->setAliases(['gb']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $input)) {
            return 1;
        }

        $validators = $this->validator;
        // we must to ensure theme exist
        $machine_name = $validators->validateMachineName($input->getOption('theme'));
        $theme = $input->getOption('theme');
        $breakpoints = $input->getOption('breakpoints');

        $this->generator->generate(
            $theme,
            $breakpoints,
            $machine_name
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --theme option.
        $theme = $input->getOption('theme');

        if (!$theme) {
            $themeHandler = $this->themeHandler;
            $themes = $themeHandler->rebuildThemeData();
            $themes['classy'] = '';

            uasort($themes, 'system_sort_modules_by_info_name');

            $theme = $io->choiceNoList(
                $this->trans('commands.generate.breakpoint.questions.theme'),
                array_keys($themes)
            );
            $input->setOption('theme', $theme);
        }

        // --breakpoints option.
        $breakpoints = $input->getOption('breakpoints');
        if (!$breakpoints) {
            $breakpoints = $this->breakpointQuestion($io);
            $input->setOption('breakpoints', $breakpoints);
        }
    }
}
