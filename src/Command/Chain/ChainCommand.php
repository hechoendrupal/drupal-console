<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Chain\ChainCommand.
 */

namespace Drupal\Console\Command\Chain;

use Drupal\Console\Command\ChainFilesTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Command;

/**
 * Class ChainCommand
 * @package Drupal\Console\Command\Chain
 */
class ChainCommand extends Command
{
    use ChainFilesTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('chain')
            ->setDescription($this->trans('commands.chain.description'))
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.chain.options.file')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = null;
        if ($input->hasOption('file')) {
            $file = $input->getOption('file');
        }

        $chainFiles = null;
        if (!$file) {
            $chainFiles = $this->getChainFiles();
        }

        if (!$chainFiles) {
            return;
        }

        $files = null;
        foreach ($chainFiles as $chainDirectory => $chainFileList) {
            foreach ($chainFileList as $chainFile) {
                $fullPath = sprintf(
                    '%s/%s',
                    $chainDirectory,
                    $chainFile
                );
                $files[] = $fullPath;
            }
        }

        $file = $io->choice(
            $this->trans('commands.chain.questions.chain-file'),
            array_values($files)
        );

        $input->setOption('file', $file);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $interactive = false;

        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $file = null;
        if ($input->hasOption('file')) {
            $file = $input->getOption('file');
        }

        if (!$file) {
            $io->error($this->trans('commands.chain.messages.missing_file'));

            return;
        }

        if (strpos($file, '~') === 0) {
            $home = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/');
            $file = realpath(preg_replace('/~/', $home, $file, 1));
        }

        if (!(strpos($file, '/') === 0)) {
            $file = sprintf('%s/%s', getcwd(), $file);
        }

        if (!file_exists($file)) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.invalid_file'),
                    $file
                )
            );

            return;
        }

        $configData = $this->getApplication()->getConfig()->getFileContents($file);
        $commands = [];
        if (array_key_exists('commands', $configData)) {
            $commands = $configData['commands'];
        }

        foreach ($commands as $command) {
            $moduleInputs = [];
            $arguments = !empty($command['arguments']) ? $command['arguments'] : [];
            $options = !empty($command['options']) ? $command['options'] : [];

            foreach ($arguments as $key => $value) {
                $moduleInputs[$key] = is_null($value) ? '' : $value;
            }

            foreach ($options as $key => $value) {
                $moduleInputs['--'.$key] = is_null($value) ? '' : $value;
            }

            $parameterOptions = $input->getOptions();
            unset($parameterOptions['file']);
            foreach ($parameterOptions as $key => $value) {
                if ($value===true) {
                    $moduleInputs['--' . $key] = true;
                }
            }

            $this->getChain()
                ->addCommand(
                    $command['command'],
                    $moduleInputs,
                    $interactive,
                    $learning
                );
        }
    }
}
