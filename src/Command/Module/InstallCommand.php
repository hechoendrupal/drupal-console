<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Core\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class InstallCommand
 *
 * @package Drupal\Console\Command\Module
 */
class InstallCommand extends Command
{
    use CommandTrait;
    use ProjectDownloadTrait;
    use ModuleTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
 * @var Validator
*/
    protected $validator;

    /**
 * @var ModuleInstaller
*/
    protected $moduleInstaller;

    /**
 * @var DrupalApi
*/
    protected $drupalApi;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * InstallCommand constructor.
     *
     * @param Site            $site
     * @param Validator       $validator
     * @param ModuleInstaller $moduleInstaller
     * @param DrupalApi       $drupalApi
     * @param Manager         $extensionManager
     * @param $appRoot
     * @param ChainQueue      $chainQueue
     */
    public function __construct(
        Site $site,
        Validator $validator,
        ModuleInstaller $moduleInstaller,
        DrupalApi $drupalApi,
        Manager $extensionManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->validator = $validator;
        $this->moduleInstaller = $moduleInstaller;
        $this->drupalApi = $drupalApi;
        $this->extensionManager = $extensionManager;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('module:install')
            ->setDescription($this->trans('commands.module.install.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.install.arguments.module')
            )
            ->addOption(
                'latest',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.latest')
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.uninstall.options.composer')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $this->modulesQuestion($io);
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $composer = $input->getOption('composer');

        $this->site->loadLegacyFile('/core/includes/bootstrap.inc');

        // check module's requirements
        $this->moduleRequirement($module, $io);

        if ($composer) {
            foreach ($module as $moduleItem) {
                $command = sprintf(
                    'composer show drupal/%s ',
                    $moduleItem
                );

                $processBuilder = new ProcessBuilder([]);
                $processBuilder->setWorkingDirectory($this->appRoot);
                $processBuilder->setArguments(explode(" ", $command));
                $process = $processBuilder->getProcess();
                $process->setTty('true');
                $process->run();

                if ($process->isSuccessful()) {
                    $io->info(
                        sprintf(
                            'Module %s was downloaded with Composer.',
                            $moduleItem
                        )
                    );
                } else {
                    $io->error(
                        sprintf(
                            'Module %s seems not to be installed with Composer. Halting.',
                            $moduleItem
                        )
                    );
                    throw new \RuntimeException($process->getErrorOutput());

                    return 0;
                }
            }

            $unInstalledModules = $module;
        } else {
            $resultList = $this->downloadModules($io, $module, $latest);

            $invalidModules = $resultList['invalid'];
            $unInstalledModules = $resultList['uninstalled'];

            if ($invalidModules) {
                foreach ($invalidModules as $invalidModule) {
                    unset($module[array_search($invalidModule, $module)]);
                    $io->error(
                        sprintf(
                            'Invalid module name: %s',
                            $invalidModule
                        )
                    );
                }
            }

            if (!$unInstalledModules) {
                $io->warning($this->trans('commands.module.install.messages.nothing'));

                return 0;
            }
        }

        try {
            $io->comment(
                sprintf(
                    $this->trans('commands.module.install.messages.installing'),
                    implode(', ', $unInstalledModules)
                )
            );

            drupal_static_reset('system_rebuild_module_data');

            $this->moduleInstaller->install($unInstalledModules, true);
            $io->success(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', $unInstalledModules)
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
