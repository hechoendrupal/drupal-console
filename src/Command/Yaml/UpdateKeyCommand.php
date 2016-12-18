<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\UpdateKeyCommand.
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
use Drupal\Console\Utils\NestedArray;

class UpdateKeyCommand extends Command
{
    use CommandTrait;

    /**
     * @var NestedArray
     */
    protected $nestedArray;

    /**
     * RebuildCommand constructor.
     * @param NestedArray $nestedArray
     */
    public function __construct(NestedArray $nestedArray)
    {
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('yaml:update:key')
            ->setDescription($this->trans('commands.yaml.update.key.description'))
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
                'yaml-new-key',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.update.value.arguments.yaml-new-key')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $yaml = new Parser();
        $dumper = new Dumper();

        $yaml_file = $input->getArgument('yaml-file');
        $yaml_key = $input->getArgument('yaml-key');
        $yaml_new_key = $input->getArgument('yaml-new-key');


        try {
            $yaml_parsed = $yaml->parse(file_get_contents($yaml_file));
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
        }

        $parents = explode(".", $yaml_key);
        $this->nestedArray->replaceKey($yaml_parsed, $parents, $yaml_new_key);

        try {
            $yaml = $dumper->dump($yaml_parsed, 10);
        } catch (\Exception $e) {
            $io->error($this->trans('commands.yaml.merge.messages.error-generating').': '.$e->getMessage());

            return;
        }

        try {
            file_put_contents($yaml_file, $yaml);
        } catch (\Exception $e) {
            $io->error($this->trans('commands.yaml.merge.messages.error-writing').': '.$e->getMessage());

            return;
        }

        $io->info(
            sprintf(
                $this->trans('commands.yaml.update.value.messages.updated'),
                $yaml_file
            )
        );
    }
}
