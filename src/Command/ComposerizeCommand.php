<?php

namespace Drupal\Console\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

class ComposerizeCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('composerize')
            ->setDescription(
                $this->trans('commands.generate.composer.description')
            )
            ->addOption(
                'show-packages',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.composer.options.show-packages')
            )
            ->addOption(
                'include-version',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.composer.options.include-version')
            )
            ->setHelp($this->trans('commands.generate.composer.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var DrupalStyle $io
         */
        $io = new DrupalStyle($input, $output);
        $includeVersion = $input->getOption('include-version');
        $showPackages = $input->getOption('show-packages');

        /**
         * @var \Drupal\Console\Extension\Manager $extensionManager
         */
        $extensionManager = $this->get('console.extension_manager');
        $modules = $extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->getList();
        $packages = [];
        $dependencies = [];
        /**
         * @var \Drupal\Core\Extension\Extension[] $module
         */
        foreach ($modules as $module) {
            $moduleDependencies = [];
            if ($this->isValid($module)) {
                $moduleDependencies = $this->extractDependencies($module, array_keys($modules));
                $packages[] = [
                    'name' => sprintf('drupal/%s', $module->getName()),
                    'version' => $this->calculateVersion($module->info['version']),
                    'type' => 'Module',
                    'dependencies' => implode(', ', array_values($moduleDependencies))
                ];
            }
            $dependencies = array_merge(
                $dependencies,
                array_keys($moduleDependencies)
            );
        }
        //        $themes = $extensionManager->discoverThemes()
        //            ->showInstalled()
        //            ->showUninstalled()
        //            ->showNoCore()
        //            ->getList(true);
        //        var_export($themes);
        //        $profiles = $extensionManager->discoverProfiles()
        //            ->showInstalled()
        //            ->showUninstalled()
        //            ->showNoCore()
        //            ->getList();
        //        var_export($profiles);
        //        var_export($profiles);

        $composerCommand = 'composer require ';
        foreach ($packages as $package) {
            $module = str_replace('drupal/', '', $package['name']);
            if (in_array($module, $dependencies)) {
                continue;
            }
            $composerCommand .= $package['name'];
            if ($includeVersion) {
                $composerCommand .= ':'.$package['version'];
            }
            $composerCommand .= ' ';
        }
        $io->newLine();
        if ($showPackages) {
            $io->comment('Detected extensions (modules and themes).');
            $tableHeader = ['Package', 'Version', 'Type', 'Dependencies'];
            $io->table($tableHeader, $packages);
        }
        $io->comment('From your project root:');
        $io->simple($this->get('console.root'));
        $io->newLine();
        $io->comment('Execute this command:');
        $io->simple($composerCommand);
    }

    private function isValid($module)
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

    private function extractDependencies($module, $modules)
    {
        if (!array_key_exists('dependencies', $module->info)) {
            return [];
        }

        $dependencies = [];
        foreach ($module->info['dependencies'] as $dependency) {
            $dependencyExploded = explode(':', $dependency);
            $moduleDependency = count($dependencyExploded)>1?$dependencyExploded[1]:$dependencyExploded[0];
            if ($space = strpos($moduleDependency, ' ')) {
                $moduleDependency = substr($moduleDependency, 0, $space);
            }

            if (!in_array($moduleDependency, $modules)) {
                continue;
            }

            if ($moduleDependency !== $module->getName()) {
                $dependencies[$moduleDependency] = 'drupal/'.$moduleDependency;
            }
        }

        return $dependencies;
    }

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
