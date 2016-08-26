<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\GetValueCommand.
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

class GetValueCommand extends Command
{
    use CommandTrait;

    protected function configure()
    {
        $this
            ->setName('yaml:get:value')
            ->setDescription($this->trans('commands.yaml.get.value.description'))
            ->addArgument(
                'yaml-file',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.get.value.arguments.yaml-file')
            )
            ->addArgument(
                'yaml-key',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.get.value.arguments.yaml-key')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $yaml = new Parser();
        $dumper = new Dumper();

        $yaml_file = $input->getArgument('yaml-file');
        $yaml_key = $input->getArgument('yaml-key');

        try {
            $yaml_parsed = $yaml->parse(file_get_contents($yaml_file), true);
        } catch (\Exception $e) {
            $io->error($this->trans('commands.yaml.merge.messages.error-parsing').': '.$e->getMessage());
            return;
        }

        if (empty($yaml_parsed)) {
            $io->info(
                sprintf(
                    $this->trans('commands.yaml.merge.messages.wrong-parse'),
                    $yaml_file
                )
            );
        } else {
            $nested_array = $this->getApplication()->getNestedArrayHelper();
            $parents = explode(".", $yaml_key);
            $yaml_value = $nested_array::getValue($yaml_parsed, $parents, $key_exists);

            if (!$key_exists) {
                $io->info(
                    sprintf(
                        $this->trans('commands.yaml.get.value.messages.invalid-key'),
                        $yaml_key,
                        $yaml_file
                    )
                );
            }

            $output->writeln($yaml_value);
        }
    }
}
