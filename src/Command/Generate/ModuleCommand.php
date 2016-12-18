<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ModuleCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\ModuleGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\DrupalApi;
use GuzzleHttp\Client;
use Drupal\Console\Utils\Site;
use GuzzleHttp\Exception\ClientException;

class ModuleCommand extends Command
{
    use ConfirmationTrait;
    use CommandTrait;

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
     * @var Client
     */
    protected $httpClient;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var string
     */
    protected $twigtemplate;


    /**
     * ModuleCommand constructor.
     * @param ModuleGenerator $generator
     * @param Validator       $validator
     * @param                 $appRoot
     * @param StringConverter $stringConverter
     * @param DrupalApi       $drupalApi
     * @param Client          $httpClient
     * @param Site            $site
     * @param               $twigtemplate
     */
    public function __construct(
        ModuleGenerator $generator,
        Validator $validator,
        $appRoot,
        StringConverter $stringConverter,
        DrupalApi $drupalApi,
        Client $httpClient,
        Site $site,
        $twigtemplate
    ) {
        $this->generator = $generator;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        $this->stringConverter = $stringConverter;
        $this->drupalApi = $drupalApi;
        $this->httpClient = $httpClient;
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
            )
            ->addOption(
                'test',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.test')
            )
            ->addOption(
                'twigtemplate',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.module.options.twigtemplate')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $module = $this->validator->validateModuleName($input->getOption('module'));

        $modulePath = $this->appRoot . $input->getOption('module-path');
        $modulePath = $this->validator->validateModulePath($modulePath, true);

        $machineName = $this->validator->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $package = $input->getOption('package');
        $moduleFile = $input->getOption('module-file');
        $featuresBundle = $input->getOption('features-bundle');
        $composer = $input->getOption('composer');
        $test = $input->getOption('test');
        $twigtemplate = $input->getOption('twigtemplate');

         // Modules Dependencies, re-factor and share with other commands
        $dependencies = $this->validator->validateModuleDependencies($input->getOption('dependencies'));
        // Check if all module dependencies are available
        if ($dependencies) {
            $checked_dependencies = $this->checkDependencies($dependencies['success'], $io);
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

        $this->generator->generate(
            $module,
            $machineName,
            $modulePath,
            $description,
            $core,
            $package,
            $moduleFile,
            $featuresBundle,
            $composer,
            $dependencies,
            $test,
            $twigtemplate
        );
    }

    /**
     * @param  array $dependencies
     * @return array
     */
    private function checkDependencies(array $dependencies, DrupalStyle $io)
    {
        $this->site->loadLegacyFile('/core/modules/system/system.module');
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
                try {
                    $response = $this->httpClient->head('https://www.drupal.org/project/' . $module);
                    $header_link = explode(';', $response->getHeader('link'));
                    if (empty($header_link[0])) {
                        $checkDependencies['no_modules'][] = $module;
                    } else {
                        $checkDependencies['drupal_modules'][] = $module;
                    }
                } catch (ClientException $e) {
                    $checkDependencies['no_modules'][] = $module;
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

        $validator = $this->validator;

        try {
            $module = $input->getOption('module') ?
              $this->validator->validateModuleName(
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
                function ($module) use ($validator) {
                    return $validator->validateModuleName($module);
                }
            );
            $input->setOption('module', $module);
        }

        try {
            $machineName = $input->getOption('machine-name') ?
              $this->validator->validateModuleName(
                  $input->getOption('machine-name')
              ) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());
        }

        if (!$machineName) {
            $machineName = $io->ask(
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
            $drupalRoot = $this->appRoot;
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

        $test = $input->getOption('test');
        if (!$test) {
            $test = $io->confirm(
                $this->trans('commands.generate.module.questions.test'),
                true
            );
            $input->setOption('test', $test);
        }

        $twigtemplate = $input->getOption('twigtemplate');
        if (!$twigtemplate) {
            $twigtemplate = $io->confirm(
                $this->trans('commands.generate.module.questions.twigtemplate'),
                true
            );
            $input->setOption('twigtemplate', $twigtemplate);
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
