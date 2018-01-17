<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallDependencyCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class InstallDependencyCommand
 *
 * @package Drupal\Console\Command\Module
 */
class InstallDependencyCommand extends Command
{
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
     * @var ModuleInstallerInterface
     */
    protected $moduleInstaller;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * InstallCommand constructor.
     *
     * @param Site                     $site
     * @param Validator                $validator
     * @param ModuleInstallerInterface $moduleInstaller
     * @param ChainQueue               $chainQueue
     */
    public function __construct(
        Site $site,
        Validator $validator,
        ModuleInstallerInterface $moduleInstaller,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->validator = $validator;
        $this->moduleInstaller = $moduleInstaller;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('module:dependency:install')
            ->setDescription($this->trans('commands.module.dependency.install.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.dependency.install.arguments.module')
            )->setAliases(['modi']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion();
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $unInstalledDependencies = $this->calculateDependencies((array)$module);

        if (!$unInstalledDependencies) {
            $this->getIo()->warning($this->trans('commands.module.dependency.install.messages.no-depencies'));
            return 0;
        }

        try {
            $this->getIo()->comment(
                sprintf(
                    $this->trans('commands.module.dependency.install.messages.installing'),
                    implode(', ', $unInstalledDependencies)
                )
            );

            drupal_static_reset('system_rebuild_module_data');

            $this->moduleInstaller->install($unInstalledDependencies, true);
            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.module.dependency.install.messages.success'),
                    implode(', ', $unInstalledDependencies)
                )
            );
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
