<?php

/**
 * @file
 * Contains \Drupal\Console\Command\InitCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\AutocompleteGenerator;
use Symfony\Component\Process\ProcessBuilder;

class InitCommand extends Command
{
    private $files = [
        [
            'source' => 'config/dist/config.yml',
            'destination' => 'config.yml',
        ],
        [
            'source' => 'config/dist/chain.yml',
            'destination' => 'chain/sample.yml',
        ],
        [
            'source' => 'config/dist/site.yml',
            'destination' => 'sites/sample.yml'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription($this->trans('commands.init.description'))
            ->addOption(
                'override',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.override')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        $message = $this->getMessageHelper();
        $basePath = __DIR__.'/../../';
        $userPath = $config->getUserHomeDir().'/.console/';
        $copiedFiles = [];

        $override = false;
        if ($input->hasOption('override')) {
            $override = $input->getOption('override');
        }

        foreach ($this->files as $file) {
            $source = $basePath.$file['source'];
            $destination = $userPath.'/'.$file['destination'];
            if ($this->copyFile($source, $destination, $override)) {
                $copiedFiles[] = $file['destination'];
            }
        }

        if ($copiedFiles) {
            $message->showCopiedFiles($output, $copiedFiles);
        }

        $this->createAutocomplete();
        $output->writeln($this->trans('application.console.messages.autocomplete'));
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
        if ($module != 'AppConsole') {
            $drupal = $this->getDrupalHelper();
            $drupal_root = $drupal->getRoot();
            $skeletonDirs[] = $drupal_root.drupal_get_path('module', $module).'/templates';
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
