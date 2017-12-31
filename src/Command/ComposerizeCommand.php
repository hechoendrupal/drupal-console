<?php

namespace Drupal\Console\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

class ComposerizeCommand extends ContainerAwareCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this
            ->setName('composerize')
            ->setDescription(
                $this->trans('commands.generate.composer.description')
            )
            ->addOption(
                'show-version',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.composer.options.version')
            )
            ->setHelp($this->trans('commands.generate.composer.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var DrupalStyle $io */
        $io = new DrupalStyle($input, $output);

        $showVersion = $input->getOption('show-version');

        /** @var \Drupal\Console\Extension\Manager $extensionManager */
        $extensionManager = $this->get('console.extension_manager');
        $modules = $extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->getList();
        $packages = [];
        /** @var \Drupal\Core\Extension\Extension[] $module */
        foreach ($modules as $module) {
            if ($this->isValid($module->info, $module->getName())) {
                $packages[] = [
                    'name' => sprintf('drupal/%s', $module->getName()),
                    'version' => $this->calculateVersion($module->info['version']),
                    'type' => 'Module'
                ];
            }
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

        $composerCommand = 'composer require ';
        foreach ($packages as $package) {
            $composerCommand .= $package['name'];
            if ($showVersion) {
                $composerCommand .= ':'.$package['version'];
            }
            $composerCommand .= ' ';
        }
        $io->newLine();
        $io->comment('Detected extensions (modules, themes and profiles).');
        $tableHeader = ['Package', 'Version', 'Type'];
        $io->table($tableHeader, $packages);
        $io->comment('Execute this command from your project root:');
        $io->text($composerCommand);
        $io->newLine();
    }


    private function isValid($info, $name) {
        if (!array_key_exists('project',$info)){
            return true;
        }

        return $info['project'] === $name;
    }

    private function calculateVersion($version) {
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