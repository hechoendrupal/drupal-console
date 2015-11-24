<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\UpdateValueCommand.
 */

namespace Drupal\Console\Command\Yaml;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;

class UpdateValueCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yaml:update:value')
            ->setDescription($this->trans('commands.yaml.update.value.description'))
            ->addArgument(
                'yaml-file',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.update.value.arguments.yaml-file')
            )
            ->addArgument(
                'yaml-key',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.update.value.arguments.yaml-key')
            )
            ->addArgument(
                'yaml-value',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.update.value.arguments.yaml-value')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yaml = new Parser();
        $dumper = new Dumper();

        $yaml_file = $input->getArgument('yaml-file');
        $yaml_key = $input->getArgument('yaml-key');
        $yaml_value = $input->getArgument('yaml-value');


        try {
            $yaml_parsed = $yaml->parse(file_get_contents($yaml_file));
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-parsing').': '.$e->getMessage().'</error>');

            return;
        }

        if (empty($yaml_parsed)) {
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.yaml.merge.messages.wrong-parse'),
                    $yaml_file
                ).'</info>'
            );
        }

        $nested_array = $this->getNestedArrayHelper();
        $parents = explode(".", $yaml_key);
        $nested_array->setValue($yaml_parsed, $parents, $yaml_value, true);


        try {
            $yaml = $dumper->dump($yaml_parsed, 10);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-generating').': '.$e->getMessage().'</error>');

            return;
        }

        try {
            file_put_contents($yaml_file, $yaml);
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-writing').': '.$e->getMessage().'</error>');

            return;
        }

        $output->writeln(
            '[+] <info>'.sprintf(
                $this->trans('commands.yaml.update.value.messages.updated'),
                $yaml_file
            ).'</info>'
        );
    }
}
