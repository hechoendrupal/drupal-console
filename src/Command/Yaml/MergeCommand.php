<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\MergeCommand.
 */

namespace Drupal\Console\Command\Yaml;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class MergeCommand extends Command
{
    use CommandTrait;

    protected function configure()
    {
        $this
            ->setName('yaml:merge')
            ->setDescription($this->trans('commands.yaml.merge.description'))
            ->addArgument(
                'yaml-destination',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.merge.arguments.yaml-destination')
            )
            ->addArgument(
                'yaml-files',
                InputArgument::IS_ARRAY,
                $this->trans('commands.yaml.merge.arguments.yaml-files')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $yaml = new Parser();
        $dumper = new Dumper();

        $final_yaml = array();
        $yaml_destination = realpath($input->getArgument('yaml-destination'));
        $yaml_files = $input->getArgument('yaml-files');

        if (count($yaml_files) < 2) {
            $io->error($this->trans('commands.yaml.merge.messages.two-files-required'));

            return;
        }

        foreach ($yaml_files as $yaml_file) {
            try {
                $yaml_parsed = $yaml->parse(file_get_contents($yaml_file));
            } catch (\Exception $e) {
                $io->error(
                    sprintf(
                        '%s: %s',
                        $this->trans('commands.yaml.merge.messages.error-parsing'),
                        $e->getMessage()
                    )
                );

                return;
            }

            if (empty($yaml_parsed)) {
                $io->error(
                    sprintf(
                        $this->trans('commands.yaml.merge.messages.wrong-parse'),
                        $yaml_file
                    )
                );
            }

            // Merge arrays
            $final_yaml = array_replace_recursive($final_yaml, $yaml_parsed);
        }

        try {
            $yaml = $dumper->dump($final_yaml, 10);
        } catch (\Exception $e) {
            $io->error(
                sprintf(
                    '%s: %s',
                    $this->trans('commands.yaml.merge.messages.error-generating'),
                    $e->getMessage()
                )
            );

            return;
        }

        try {
            file_put_contents($yaml_destination, $yaml);
        } catch (\Exception $e) {
            $io->error(
                sprintf(
                    '%s: %s',
                    $this->trans('commands.yaml.merge.messages.error-writing'),
                    $e->getMessage()
                )
            );

            return;
        }

        $io->success(
            sprintf(
                $this->trans('commands.yaml.merge.messages.merged'),
                $yaml_destination
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $validator_filename = function ($value) use ($io) {
            if (!strlen(trim($value)) || !is_file($value)) {
                $io->error($this->trans('commands.common.errors.invalid-file-path'));

                return false;
            }

            return $value;
        };

        // --yaml-destination option
        $yaml_destination = $input->getArgument('yaml-destination');
        if (!$yaml_destination) {
            while (true) {
                $yaml_destination = $io->ask(
                    $this->trans('commands.yaml.merge.questions.yaml-destination'),
                    '',
                    $validator_filename
                );

                if ($yaml_destination) {
                    break;
                }
            }

            $input->setArgument('yaml-destination', $yaml_destination);
        }

        $yaml_files = $input->getArgument('yaml-files');
        if (!$yaml_files) {
            $yaml_files = array();

            while (true) {
                // Set the string key based on among files provided
                if (count($yaml_files) >= 2) {
                    $questionStringKey = 'commands.yaml.merge.questions.other-file';
                } else {
                    $questionStringKey = 'commands.yaml.merge.questions.file';
                }

                $yaml_file = $io->ask(
                    $this->trans($questionStringKey),
                    '',
                    function ($file) use ($yaml_files, $io) {
                        if (count($yaml_files) < 2 && empty($file)) {
                            $io->error($this->trans('commands.yaml.merge.questions.invalid-file'));
                            return false;
                        } elseif (!empty($file) && in_array($file, $yaml_files)) {
                            $io->error(
                                sprintf($this->trans('commands.yaml.merge.questions.file-already-added'), $file)
                            );

                            return false;
                        } elseif ($file == '') {
                            return true;
                        } else {
                            return $file;
                        }
                    }
                );

                if ($yaml_file && !is_string($yaml_file)) {
                    break;
                }

                if ($yaml_file) {
                    $yaml_files[] = realpath($yaml_file);
                }
            }

            $input->setArgument('yaml-files', $yaml_files);
        }
    }
}
