<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Console\ConfigDebugCommand.
 */

namespace Drupal\Console\Command\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\AutocompleteGenerator;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class ConfigDebugCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('console:config:debug')
            ->setDescription($this->trans('commands.console.config.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $nestedArray = $this->getNestedArrayHelper();

        $application = $this->getApplication();
        $config = $application->getConfig();

        $userPath = sprintf('%s/.console/', $config->getUserHomeDir());

        $configApplication = $config->get('application');

        unset($configApplication['autowire']);
        unset($configApplication['languages']);
        unset($configApplication['default']);

        $configApplicationFlatten = array();
        $keyFlatten = '';
        $nestedArray->yamlFlattenArray($configApplication, $configApplicationFlatten, $keyFlatten);


        $tableHeader = [
            $this->trans('commands.console.config.debug.messages.config-key'),
            $this->trans('commands.console.config.debug.messages.config-value'),
        ];

        $tableRows = [];
        foreach ($configApplicationFlatten as $yamlKey => $yamlValue) {
            $tableRows[] = [
                $yamlKey,
                $yamlValue
            ];
        }

        $io->info(
            sprintf(
                $this->trans('commands.console.config.debug.messages.config-file'),
                $userPath . 'config.yml'
            )
        );

        $io->table($tableHeader, $tableRows, 'compact');
    }

    protected function createAutocomplete()
    {
        $generator = new AutocompleteGenerator();
        $generator->setHelperSet($this->getHelperSet());

        $application = $this->getApplication();
        $config = $application->getConfig();
        $userPath = $config->getUserHomeDir().'/.console/';

        $processBuilder = new ProcessBuilder(array('bash'));
        $process = $processBuilder->getProcess();
        $process->setCommandLine('echo $_');
        $process->run();
        $fullPathExecutable = explode('/', $process->getOutput());
        $executable = trim(end($fullPathExecutable));
        $process->stop();

        $generator->generate($userPath, $executable);
    }

    protected function getSkeletonDirs()
    {
        $module = $this->getModule();
        if ($module != 'Console') {
            $drupal = $this->getDrupalHelper();
            $drupalRoot = $drupal->getRoot();
            $skeletonDirs[] = $drupalRoot.drupal_get_path('module', $module).'/templates';
        }

        $skeletonDirs[] = __DIR__.'/../../templates';

        return $skeletonDirs;
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $override
     * @return bool
     */
    public function copyFile($source, $destination, $override)
    {
        if (file_exists($destination) && !$override) {
            return false;
        }

        $filePath = dirname($destination);
        if (!is_dir($filePath)) {
            mkdir($filePath);
        }

        return copy(
            $source,
            $destination
        );
    }
}
