<?php

namespace Drupal\Console\Command;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\DrupalFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Component\Serialization\Json;

/**
 * Class ComposerizeCommand
 *
 * @package Drupal\Console\Command
 */
class ComposerizeCommand extends ContainerAwareCommand
{

    /**
     * @var array
     */
    protected $corePackages = [];

    /**
     * @var array
     */
    protected $packages = [];

    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @var DrupalFinder
     */
    protected $drupalFinder = null;

    /**
     * @var Manager $extensionManager
     */
    protected $extensionManager = null;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('composerize')
            ->setDescription(
                $this->trans('commands.composerize.description')
            )
            ->addOption(
                'show-packages',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.composerize.options.show-packages')
            )
            ->addOption(
                'include-version',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.composerize.options.include-version')
            )
            ->setHelp($this->trans('commands.composerize.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $includeVersion = $input->getOption('include-version');
        $showPackages = $input->getOption('show-packages')?:false;

        $this->drupalFinder = $this->get('console.drupal_finder');

        $this->extensionManager = $this->get('console.extension_manager');
        $this->extractCorePackages();

        $this->processProfiles();
        $this->processModules();
        $this->processThemes();

        $types = [
            'profile',
            'module',
            'theme'
        ];

        $composerCommand = 'composer require ';
        foreach ($types as $type) {
            $packages = $this->packages[$type];
            if (!$packages) {
                continue;
            }

            if ($showPackages) {
                $this->getIo()->comment($this->trans('commands.composerize.messages.'.$type));
                $tableHeader = [
                    $this->trans('commands.composerize.messages.name'),
                    $this->trans('commands.composerize.messages.version'),
                    $this->trans('commands.composerize.messages.dependencies')
                ];
                $this->getIo()->table($tableHeader, $packages);
            }
            foreach ($packages as $package) {
                $module = str_replace('drupal/', '', $package['name']);
                if (array_key_exists($module, $this->dependencies)) {
                    continue;
                }
                $composerCommand .= $package['name'];
                if ($includeVersion) {
                    $composerCommand .= ':' . $package['version'];
                }
                $composerCommand .= ' ';
            }
        }
        $this->getIo()->comment($this->trans('commands.composerize.messages.from'));
        $this->getIo()->simple($this->drupalFinder->getComposerRoot());
        $this->getIo()->newLine();
        $this->getIo()->comment($this->trans('commands.composerize.messages.execute'));
        $this->getIo()->simple($composerCommand);
        $this->getIo()->newLine();
        $this->getIo()->comment($this->trans('commands.composerize.messages.ignore'));

        $webRoot = str_replace(
            $this->drupalFinder->getComposerRoot() . '/',
            '',
            $this->drupalFinder->getDrupalRoot() . '/'
        );

        $this->getIo()->writeln(
            [
                ' vendor/',
                ' '.$webRoot.'modules/contrib',
                ' '.$webRoot.'themes/contrib',
                ' '.$webRoot.'profiles/contrib'
            ]
        );
    }

    private function extractCorePackages()
    {
        $this->corePackages['module'] = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showUninstalled()
            ->showCore()
            ->getList(true);

        $this->corePackages['theme'] = $this->extensionManager->discoverThemes()
            ->showInstalled()
            ->showUninstalled()
            ->showCore()
            ->getList(true);

        $this->corePackages['profile'] = $this->extensionManager->discoverProfiles()
            ->showInstalled()
            ->showUninstalled()
            ->showCore()
            ->getList(true);
    }

    private function processProfiles()
    {
        $type = 'profile';
        $profiles = $this->extensionManager->discoverProfiles()
            ->showNoCore()
            ->showInstalled()
            ->getList();

        /**
         * @var \Drupal\Core\Extension\Extension[] $module
         */
        foreach ($profiles as $profile) {
            if (!$this->isValidModule($profile)) {
                continue;
            }

            $dependencies = $this->extractDependencies(
                $profile,
                []
            );

            $this->packages[$type][] = [
                'name' => sprintf('drupal/%s', $profile->getName()),
                'version' => $this->calculateVersion($profile->info['version']),
                'dependencies' => implode(PHP_EOL, array_values($dependencies))
            ];

            $this->dependencies = array_merge(
                $this->dependencies,
                $dependencies?$dependencies:[]
            );
        }
    }

    private function processModules()
    {
        $type = 'module';
        $modules = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->getList();

        /**
         * @var \Drupal\Core\Extension\Extension[] $module
         */
        foreach ($modules as $module) {
            if (!$this->isValidModule($module)) {
                continue;
            }

            $dependencies = $this->extractDependencies(
                $module,
                array_keys($modules)
            );
            $this->packages[$type][] = [
                'name' => sprintf('drupal/%s', $module->getName()),
                'version' => $this->calculateVersion($module->info['version']),
                'dependencies' => implode(PHP_EOL, array_values($dependencies))
            ];

            $this->dependencies = array_merge(
                $this->dependencies,
                $dependencies?$dependencies:[]
            );
        }
    }

    private function processThemes()
    {
        $type = 'theme';
        $themes = $this->extensionManager->discoverThemes()
            ->showInstalled()
            ->showNoCore()
            ->getList();
        /**
         * @var \Drupal\Core\Extension\Extension[] $module
         */
        foreach ($themes as $theme) {
            if (!$this->isValidTheme($theme)) {
                continue;
            }
            $this->packages[$type][] = [
                'name' => sprintf('drupal/%s', $theme->getName()),
                'version' => $this->calculateVersion($theme->info['version']),
                'dependencies' => ''
            ];
        }
    }

    /**
     * @param $module
     * @return bool
     */
    private function isValidModule($module)
    {
        if (strpos($module->getPath(), 'modules/custom') === 0) {
            return false;
        }

        if (!array_key_exists('project', $module->info)) {
            return true;
        }

        if (!array_key_exists('project', $module->info)) {
            return true;
        }

        return $module->info['project'] === $module->getName();
    }

    /**
     * @param $module
     * @return bool
     */
    private function isValidTheme($module)
    {
        if (strpos($module->getPath(), 'themes/custom') === 0) {
            return false;
        }

        return true;
    }

    private function isValidDependency($moduleDependency, $extension, $extensions)
    {
        if (in_array($moduleDependency, $this->corePackages['module'])) {
            return false;
        }

        if ($extensions && !in_array($moduleDependency, $extensions)) {
            return false;
        }

        if ($moduleDependency !== $extension->getName()) {
            return true;
        }
    }

    /**
     * @param $extension
     * @param $extensions
     * @return array
     */
    private function extractDependencies($extension, $extensions)
    {
        if (!array_key_exists('dependencies', $extension->info)) {
            return [];
        }

        $dependencies = [];

        // Dependencies defined at info.yml
        foreach ($extension->info['dependencies'] as $dependency) {
            $dependencyExploded = explode(':', $dependency);
            $moduleDependency = count($dependencyExploded)>1?$dependencyExploded[1]:$dependencyExploded[0];

            if ($space = strpos($moduleDependency, ' ')) {
                $moduleDependency = substr($moduleDependency, 0, $space);
            }

            if ($this->isValidDependency($moduleDependency, $extension, $extensions)) {
                $dependencies[$moduleDependency] = 'drupal/'.$moduleDependency;
            }
        }

        // Dependencies defined at composer.json
        $composer = $this->readComposerFile($extension->getPath() . '/composer.json');
        if (array_key_exists('require', $composer)) {
            foreach (array_keys($composer['require']) as $package) {
                if (strpos($package, 'drupal/') !== 0) {
                    continue;
                }
                $moduleDependency = str_replace('drupal/', '', $package);
                if ($this->isValidDependency($moduleDependency, $extension, $extensions)) {
                    $dependencies[$moduleDependency] = $package;
                }
            }
        }

        return $dependencies;
    }

    protected function readComposerFile($file)
    {
        if (!file_exists($file)) {
            return [];
        }

        return Json::decode(file_get_contents($file));
    }

    /**
     * @param $version
     * @return mixed
     */
    private function calculateVersion($version)
    {
        $replaceKeys = [
            '8.x-' => '',
            '8.' => ''
        ];
        return str_replace(
            array_keys($replaceKeys),
            array_values($replaceKeys),
            $version
        );
    }
}
