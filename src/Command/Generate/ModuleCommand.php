<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ModuleCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Site;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\ModuleGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\DrupalApi;
use Webmozart\PathUtil\Path;
use Drupal\Console\Core\Utils\ChainQueue;

class ModuleCommand extends Command
{
    use ConfirmationTrait;

    /**
     * @var ModuleGenerator
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
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * @var string
     */
    protected $twigtemplate;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var Site
     */
    protected $site;


    /**
     * ModuleCommand constructor.
     *
     * @param ModuleGenerator $generator
     * @param Validator       $validator
     * @param $appRoot
     * @param StringConverter $stringConverter
     * @param DrupalApi       $drupalApi
     * @param ChainQueue      $chainQueue
     * @param Site            $site
     * @param $twigtemplate
     */
    public function __construct(
        ModuleGenerator $generator,
        Validator $validator,
        $appRoot,
        StringConverter $stringConverter,
        DrupalApi $drupalApi,
        ChainQueue $chainQueue,
        Site $site,
        $twigtemplate = null
    ) {
        $this->generator = $generator;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        $this->stringConverter = $stringConverter;
        $this->drupalApi = $drupalApi;
        $this->chainQueue = $chainQueue;
        $this->site = $site;
        $this->twigtemplate = $twigtemplate;
        parent::__construct();
    }

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
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.module')
            )
            ->addOption(
                'machine-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.machine-name')
            )
            ->addOption(
                'module-path',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.module-path')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.description')
            )
            ->addOption(
                'core',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.core')
            )
            ->addOption(
                'package',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.package')
            )
            ->addOption(
                'module-file',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.module.options.module-file')
            )
            ->addOption(
                'features-bundle',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.features-bundle')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.module.options.composer')
            )
            ->addOption(
                'dependencies',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.dependencies'),
                ''
            )
            ->addOption(
                'test',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.test')
            )
            ->addOption(
                'twigtemplate',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.twigtemplate')
            )
            ->setAliases(['gm']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $this->validator->validateModuleName($input->getOption('module'));

        // Get the profile path and define a profile path if it is null
        // Check that it is an absolute path or otherwise create an absolute path using appRoot
        $modulePath = $input->getOption('module-path');
        if(is_null($modulePath)) {
            $uri = $this->site->getMultisiteName($input);
            $defaultModulePath = 'modules/custom';
            $modulePath = $this->site->multisiteMode($uri)? 'sites/'.$this->site->getMultisiteDir($uri).'/'.$defaultModulePath : $defaultModulePath;
        }
        $modulePath = Path::isAbsolute($modulePath) ? $modulePath : Path::makeAbsolute($modulePath, $this->appRoot);
        $modulePath = $this->validator->validateModulePath($modulePath, true);

        $machineName = $input->getOption('machine-name') ?
            $this->validator->validateMachineName($input->getOption('machine-name'))
            :$this->stringConverter->createMachineName($module);

        $description = $input->getOption('description')?:$this->trans('commands.generate.module.suggestions.my-awesome-module');
        $core = $input->getOption('core')?:'8.x';
        $package = $input->getOption('package')?:'Custom';
        $moduleFile = $input->getOption('module-file');
        $featuresBundle = $input->getOption('features-bundle');
        $composer = $input->getOption('composer');
        $dependencies = $this->validator->validateExtensions(
            $input->getOption('dependencies'),
            'module',
            $this->getIo()
        );
        $test = $input->getOption('test');
        $twigTemplate = $input->getOption('twigtemplate');

        $this->generator->generate([
            'module' => $module,
            'machine_name' => $machineName,
            'module_path' => $modulePath,
            'description' => $description,
            'core' => $core,
            'package' => $package,
            'module_file' => $moduleFile,
            'features_bundle' => $featuresBundle,
            'composer' => $composer,
            'dependencies' => $dependencies,
            'test' => $test,
            'twig_template' => $twigTemplate,
        ]);

        if ($composer) {
            $this->chainQueue
              ->addCommand(
                'generate:composer', [
                '--module' => $machineName,
                '--name' => 'drupal/' . $machineName,
                '--type' => 'drupal-module',
                '--description' => $description,
                '--keywords' => 'Drupal',
                '--license' => 'GPL-2.0+',
                '--homepage' => 'https://www.drupal.org/project/' . $machineName,
                '--minimum-stability' => 'dev',
                '--support' => [
                  '"channel":"issues", "url":"https://www.drupal.org/project/issues/' . $machineName . '"',
                  '"channel":"source", "url":"http://cgit.drupalcode.org/' . $machineName . '"',
                ],
                '--no-interaction' => 'yes',
              ],
                false
              );
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator = $this->validator;

        try {
            $module = $input->getOption('module') ?
              $this->validator->validateModuleName(
                  $input->getOption('module')
              ) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());

            return 1;
        }

        if (!$module) {
            $module = $this->getIo()->ask(
                $this->trans('commands.generate.module.questions.module'),
                null,
                function ($module) use ($validator) {
                    return $validator->validateModuleName($module);
                }
            );
            $input->setOption('module', $module);
        }

        try {
            $machineName = $input->getOption('machine-name') ?
                $validator->validateModuleName(
                  $input->getOption('machine-name')
              ) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());
        }

        if (!$machineName) {
            $machineName = $this->getIo()->ask(
                $this->trans('commands.generate.module.questions.machine-name'),
                $this->stringConverter->createMachineName($module),
                function ($machine_name) use ($validator) {
                    return $validator->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machineName);
        }

        $modulePath = $input->getOption('module-path');
        if (!$modulePath) {
            $uri = $this->site->getMultisiteName($input);
            $defaultModulePath = 'modules/custom';
            $modulePath = $this->getIo()->ask(
                $this->trans('commands.generate.module.questions.module-path'),
                $this->site->multisiteMode($uri)? 'sites/'.$this->site->getMultisiteDir($uri).'/'.$defaultModulePath : $defaultModulePath,
                function ($modulePath) use ($machineName) {
                    $fullPath = Path::isAbsolute($modulePath) ? $modulePath : Path::makeAbsolute($modulePath, $this->appRoot);
                    $fullPath = $fullPath.'/'.$machineName;
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
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.module.questions.description'),
                $this->trans('commands.generate.module.suggestions.my-awesome-module')
            );
        }
        $input->setOption('description', $description);

        $package = $input->getOption('package');
        if (!$package) {
            $package = $this->getIo()->ask(
                $this->trans('commands.generate.module.questions.package'),
                'Custom'
            );
        }
        $input->setOption('package', $package);

        $core = $input->getOption('core');
        if (!$core) {
            $core = $this->getIo()->ask(
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
            $moduleFile = $this->getIo()->confirm(
                $this->trans('commands.generate.module.questions.module-file'),
                true
            );
            $input->setOption('module-file', $moduleFile);
        }

        $featuresBundle = $input->getOption('features-bundle');
        if (!$featuresBundle) {
            $featuresSupport = $this->getIo()->confirm(
                $this->trans('commands.generate.module.questions.features-support'),
                false
            );
            if ($featuresSupport) {
                $featuresBundle = $this->getIo()->ask(
                    $this->trans('commands.generate.module.questions.features-bundle'),
                    'default'
                );
            }
            $input->setOption('features-bundle', $featuresBundle);
        }

        $composer = $input->getOption('composer');
        if (!$composer) {
            $composer = $this->getIo()->confirm(
                $this->trans('commands.generate.module.questions.composer'),
                true
            );
            $input->setOption('composer', $composer);
        }

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            $addDependencies = $this->getIo()->confirm(
                $this->trans('commands.generate.module.questions.dependencies'),
                false
            );
            if ($addDependencies) {
                $dependencies = $this->getIo()->ask(
                    $this->trans('commands.generate.module.options.dependencies')
                );
            }
            $input->setOption('dependencies', $dependencies);
        }

        $test = $input->getOption('test');
        if (!$test) {
            $test = $this->getIo()->confirm(
                $this->trans('commands.generate.module.questions.test'),
                true
            );
            $input->setOption('test', $test);
        }

        $twigtemplate = $input->getOption('twigtemplate');
        if (!$twigtemplate) {
            $twigtemplate = $this->getIo()->confirm(
                $this->trans('commands.generate.module.questions.twigtemplate'),
                true
            );
            $input->setOption('twigtemplate', $twigtemplate);
        }
    }
}
