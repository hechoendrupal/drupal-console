<?php

namespace Drupal\Console\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

class ComposerizeCommand extends ContainerAwareCommand
{
    protected $packages = [];

    protected $dependencies = [];

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
                'hide-packages',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.composerize.options.hide-packages')
            )
            ->addOption(
                'include-version',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.composerize.options.include-version')
            )
            ->setHelp($this->trans('commands.composerize.help'));
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
        $hidePackages = $input->getOption('hide-packages')?:false;

        /**
         * @var \Drupal\Console\Extension\Manager $extensionManager
         */
        $extensionManager = $this->get('console.extension_manager');
        $this->processModules($extensionManager);

        $types = [
          'module',
          'theme'
        ];

        $composerCommand = 'composer require ';
        foreach ($types as $type) {
            $packages = $this->packages[$type];
            if (!$packages) {
                continue;
            }
            if (!$hidePackages) {
                $io->comment(ucfirst($type).'(s) detected.');
                $tableHeader = ['Name', 'Version', 'Dependencies'];
                $io->table($tableHeader, $packages);
            }
            foreach ($packages as $package) {
                $module = str_replace('drupal/', '', $package['name']);
                if (in_array($module, $this->dependencies[$type])) {
                    continue;
                }
                $composerCommand .= $package['name'];
                if ($includeVersion) {
                    $composerCommand .= ':' . $package['version'];
                }
                $composerCommand .= ' ';
            }
        }
        $io->comment('From your project root:');
        $io->simple($this->get('console.root'));
        $io->newLine();
        $io->comment('Execute this command:');
        $io->simple($composerCommand);
        $io->newLine();
        $io->comment('To ignore third party libraries, modules and themes add to your .gitignore file:');
        $io->writeln(
            [
                ' /vendor/',
                ' /modules/contrib',
                ' /themes/contrib'
            ]
        );
    }

    private function processModules(Manager $extensionManager)
    {
        $type = 'module';
        $modules = $extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->getList();

        /**
         * @var \Drupal\Core\Extension\Extension[] $module
         */
        foreach ($modules as $module) {
            $moduleDependencies = [];
            if ($this->isValidModule($module)) {
                $moduleDependencies = $this->extractDependencies(
                    $module,
                    array_keys($modules)
                );
                $this->packages[$type][] = [
                    'name' => sprintf('drupal/%s', $module->getName()),
                    'version' => $this->calculateVersion($module->info['version']),
                    'dependencies' => implode(', ', array_values($moduleDependencies))
                ];
            }
            $this->dependencies[$type] = array_merge(
                $this->dependencies[$type],
                array_keys($moduleDependencies)
            );
        }
    }

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
