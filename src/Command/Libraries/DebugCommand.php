<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Libraries\DebugCommand.
 */

namespace Drupal\Console\Command\Libraries;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Helper\HelperTrait;

class DebugCommand extends Command
{
    use HelperTrait;
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('libraries:debug')
            ->setDescription($this->trans('commands.libraries.debug.description'))
            ->addArgument(
                'group',
                InputArgument::OPTIONAL,
                $this->trans('commands.libraries.debug.options.name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $group = $input->getArgument('group');
        if (!$group) {
            $groups = $this->getAllLibraries();

            $tableHeader = [
                $this->trans('commands.libraries.debug.messages.name'),
            ];

            $io->table($tableHeader, $groups, 'compact');
        } else {
            $librariesData = $this->getLibraryByName($group);

            foreach ($librariesData as $key => $libraries) {
                $io->comment($key);
                $io->writeln(Yaml::encode($libraries));
            }
        }
    }

    private function getAllLibraries()
    {
        $modules = $this->getDrupalService('module_handler')->getModuleList();
        $themes = $this->getDrupalService('theme_handler')->rebuildThemeData();

        $extensions = array_merge($modules, $themes);
        $libraryDiscovery = $this->getDrupalService('library.discovery');
        $drupal = $this->get('site');
        ;
        $root = $drupal->getRoot();
        foreach ($extensions as $extension_name => $extension) {
            $library_file = $extension->getPath() . '/' . $extension_name . '.libraries.yml';
            if (is_file($root . '/' . $library_file)) {
                $libraries[$extension_name] = $libraryDiscovery->getLibrariesByExtension($extension_name);
            }
        }
        $extensionLibraries = array_keys($libraries);
        return $extensionLibraries;
    }

    /**
     * @param $group    String
     */
    private function getLibraryByName($group)
    {
        $libraryDiscovery = $this->getDrupalService('library.discovery');
        $library = $libraryDiscovery->getLibrariesByExtension($group);
        return  $library;
    }
}
