<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ThemeCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ThemeRegionTrait;
use Drupal\Console\Command\Shared\ThemeBreakpointTrait;
use Drupal\Console\Generator\ThemeGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Class ThemeCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class ThemeCommand extends Command
{
    use ConfirmationTrait;
    use ThemeRegionTrait;
    use ThemeBreakpointTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var ThemeGenerator
*/
    protected $generator;

    /**
 * @var Validator
*/
    protected $validator;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * ThemeCommand constructor.
     *
     * @param Manager         $extensionManager
     * @param ThemeGenerator  $generator
     * @param Validator       $validator
     * @param $appRoot
     * @param ThemeHandler    $themeHandler
     * @param Site            $site
     * @param StringConverter $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        ThemeGenerator $generator,
        Validator $validator,
        $appRoot,
        ThemeHandler $themeHandler,
        Site $site,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        $this->themeHandler = $themeHandler;
        $this->site = $site;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }


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
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.theme')
            )
            ->addOption(
                'machine-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.machine-name')
            )
            ->addOption(
                'theme-path',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.theme-path')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.description')
            )
            ->addOption('core', null, InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.theme.options.core'))
            ->addOption(
                'package',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.package')
            )
            ->addOption(
                'global-library',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.global-library')
            )
            ->addOption(
                'libraries',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.libraries')
            )
            ->addOption(
                'base-theme',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.base-theme')
            )
            ->addOption(
                'regions',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.regions')
            )
            ->addOption(
                'breakpoints',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.breakpoints')
            )
            ->setAliases(['gt']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $theme = $this->validator->validateModuleName($input->getOption('theme'));
        $theme_path = $this->appRoot . $input->getOption('theme-path');
        $theme_path = $this->validator->validateModulePath($theme_path, true);

        $machine_name = $this->validator->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $package = $input->getOption('package');
        $base_theme = $input->getOption('base-theme');
        $global_library = $input->getOption('global-library');
        $libraries = $input->getOption('libraries');
        $regions = $input->getOption('regions');
        $breakpoints = $input->getOption('breakpoints');

        $this->generator->generate(
            $theme,
            $machine_name,
            $theme_path,
            $description,
            $core,
            $package,
            $base_theme,
            $global_library,
            $libraries,
            $regions,
            $breakpoints
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        try {
            $theme = $input->getOption('theme') ? $this->validator->validateModuleName($input->getOption('theme')) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return 1;
        }

        if (!$theme) {
            $validators = $this->validator;
            $theme = $io->ask(
                $this->trans('commands.generate.theme.questions.theme'),
                '',
                function ($theme) use ($validators) {
                    return $validators->validateModuleName($theme);
                }
            );
            $input->setOption('theme', $theme);
        }

        try {
            $machine_name = $input->getOption('machine-name') ? $this->validator->validateModule($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());

            return 1;
        }

        if (!$machine_name) {
            $machine_name = $io->ask(
                $this->trans('commands.generate.theme.questions.machine-name'),
                $this->stringConverter->createMachineName($theme),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machine_name);
        }

        $theme_path = $input->getOption('theme-path');
        if (!$theme_path) {
            $drupalRoot = $this->appRoot;
            $theme_path = $io->ask(
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
            $description = $io->ask(
                $this->trans('commands.generate.theme.questions.description'),
                $this->trans('commands.generate.theme.suggestions.my-awesome-theme')
            );
            $input->setOption('description', $description);
        }

        $package = $input->getOption('package');
        if (!$package) {
            $package = $io->ask(
                $this->trans('commands.generate.theme.questions.package'),
                $this->trans('commands.generate.theme.suggestions.other')
            );
            $input->setOption('package', $package);
        }

        $core = $input->getOption('core');
        if (!$core) {
            $core = $io->ask(
                $this->trans('commands.generate.theme.questions.core'),
                '8.x'
            );
            $input->setOption('core', $core);
        }

        $base_theme = $input->getOption('base-theme');
        if (!$base_theme) {
            $themes = $this->themeHandler->rebuildThemeData();
            $themes['false'] ='';

            uasort($themes, 'system_sort_modules_by_info_name');

            $base_theme = $io->choiceNoList(
                $this->trans('commands.generate.theme.options.base-theme'),
                array_keys($themes)
            );
            $input->setOption('base-theme', $base_theme);
        }

        $global_library = $input->getOption('global-library');
        if (!$global_library) {
            $global_library = $io->ask(
                $this->trans('commands.generate.theme.questions.global-library'),
                'global-styling'
            );
            $input->setOption('global-library', $global_library);
        }


        // --libraries option.
        $libraries = $input->getOption('libraries');
        if (!$libraries) {
            if ($io->confirm(
                $this->trans('commands.generate.theme.questions.library-add'),
                true
            )
            ) {
                // @see \Drupal\Console\Command\Shared\ThemeRegionTrait::libraryQuestion
                $libraries = $this->libraryQuestion($io);
                $input->setOption('libraries', $libraries);
            }
        }

        // --regions option.
        $regions = $input->getOption('regions');
        if (!$regions) {
            if ($io->confirm(
                $this->trans('commands.generate.theme.questions.regions'),
                true
            )
            ) {
                // @see \Drupal\Console\Command\Shared\ThemeRegionTrait::regionQuestion
                $regions = $this->regionQuestion($io);
                $input->setOption('regions', $regions);
            }
        }

        // --breakpoints option.
        $breakpoints = $input->getOption('breakpoints');
        if (!$breakpoints) {
            if ($io->confirm(
                $this->trans('commands.generate.theme.questions.breakpoints'),
                true
            )
            ) {
                // @see \Drupal\Console\Command\Shared\ThemeRegionTrait::regionQuestion
                $breakpoints = $this->breakpointQuestion($io);
                $input->setOption('breakpoints', $breakpoints);
            }
        }
    }
}
