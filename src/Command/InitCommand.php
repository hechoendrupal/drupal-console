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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class InitCommand extends BaseCommand
{
    use CommandTrait;

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
        $io = new DrupalStyle($input, $output);

        $application = $this->getApplication();
        $config = $application->getConfig();
        $showFileHelper = $application->getShowFileHelper();
        $userPath = sprintf('%s/.console/', $config->getUserHomeDir());
        $copiedFiles = [];

        $override = false;
        if ($input->hasOption('override')) {
            $override = $input->getOption('override');
        }

        $finder = new Finder();
        $finder->in(sprintf('%sconfig/dist/', $application->getDirectoryRoot()));
        $finder->files();

        foreach ($finder as $configFile) {
            $source = sprintf(
                '%s/config/dist/%s',
                $application->getDirectoryRoot(),
                $configFile->getRelativePathname()
            );
            $destination = sprintf(
                '%s/%s',
                $userPath,
                $configFile->getRelativePathname()
            );
            if ($this->copyFile($source, $destination, $override)) {
                $copiedFiles[] = $configFile->getRelativePathname();
            }
        }

        if ($copiedFiles) {
            $showFileHelper->copiedFiles($io, $copiedFiles);
        }

        $this->createAutocomplete();
        $io->newLine(1);
        $io->writeln($this->trans('application.messages.autocomplete'));
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
