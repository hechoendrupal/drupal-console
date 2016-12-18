<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\UnsetKeyCommand.
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

class UnsetKeyCommand extends Command
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
            ->setName('yaml:unset:key')
            ->setDescription($this->trans('commands.yaml.unset.key.description'))
            ->addArgument(
                'yaml-file',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.unset.value.arguments.yaml-file')
            )
            ->addArgument(
                'yaml-key',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.unset.value.arguments.yaml-key')
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
        $this->nestedArray->unsetValue($yaml_parsed, $parents);

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
                $this->trans('commands.yaml.unset.value.messages.unset'),
                $yaml_file
            )
        );
    }
}
