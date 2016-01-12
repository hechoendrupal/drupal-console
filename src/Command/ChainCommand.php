<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ChainCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ChainCommand
 * @package Drupal\Console\Command
 */
class ChainCommand extends Command
{
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $interactive = false;

        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

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

            $this->getChain()
                ->addCommand($command['command'], $moduleInputs, $interactive, $learning);
        }
    }
}
