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
use Drupal\Console\Style\DrupalStyle;

class InitCommand extends Command
{
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
        $message = $this->getMessageHelper();
        $userPath = sprintf('%s/.console/', $config->getUserHomeDir());
        $copiedFiles = [];

        $override = false;
        if ($input->hasOption('override')) {
            $override = $input->getOption('override');
        }

        $finder = new Finder();
        $finder->in(sprintf('%s/config/dist', $application->getDirectoryRoot()));
        $finder->name("*.yml");

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
            $message->showCopiedFiles($io, $copiedFiles);
        }

        $this->createAutocomplete();
        $io->newLine(1);
        $io->writeln($this->trans('application.console.messages.autocomplete'));
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
