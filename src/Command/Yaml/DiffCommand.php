<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Yaml\DiffCommand.
 */

namespace Drupal\Console\Command\Yaml;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;

class DiffCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yaml:diff')
            ->setDescription($this->trans('commands.yaml.diff.description'))
            ->addArgument(
                'yaml-left',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.diff.arguments.yaml-left')
            )
            ->addArgument(
                'yaml-right',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.diff.arguments.yaml-right')
            )
            ->addOption(
                'stats',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.yaml.diff.options.stats')
            )
            ->addOption(
                'negate',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.yaml.diff.options.negate')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.yaml.diff.options.limit')
            )
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.yaml.diff.options.offset')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yaml = new Parser();
        $message = $this->getMessageHelper();

        $yaml_left = $input->getArgument('yaml-left');
        $yaml_right = $input->getArgument('yaml-right');

        $stats = $input->getOption('stats');

        $negate = $input->getOption('negate');

        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        if ($negate == 1 || $negate == 'TRUE') {
            $negate = true;
        } else {
            $negate = false;
        }

        try {
            $yaml_left_parsed = $yaml->parse(file_get_contents($yaml_left));

            if (empty($yaml_left_parsed)) {
                $output->writeln(
                    '[+] <info>'.sprintf(
                        $this->trans('commands.yaml.merge.messages.wrong-parse'),
                        $yaml_left
                    ).'</info>'
                );
            }

            $yaml_right_parsed = $yaml->parse(file_get_contents($yaml_right));

            if (empty($yaml_right_parsed)) {
                $output->writeln(
                    '[+] <info>'.sprintf(
                        $this->trans('commands.yaml.merge.messages.wrong-parse'),
                        $yaml_right
                    ).'</info>'
                );
            }
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$this->trans('commands.yaml.merge.messages.error-parsing').': '.$e->getMessage().'</error>');

            return;
        }

        $nested_array = $this->getNestedArrayHelper();

        $statisticts = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
        $diff = $nested_array->arrayDiff($yaml_left_parsed, $yaml_right_parsed, $negate, $statisticts);


        $table = $this->getTableHelper();
        $table->setlayout($table::LAYOUT_COMPACT);

        if ($stats) {
            $message->addInfoMessage(
                sprintf(
                    $this->trans('commands.yaml.diff.messages.total'),
                    $statisticts['total']
                )
            );

            $message->addInfoMessage(
                sprintf(
                    $this->trans('commands.yaml.diff.messages.diff'),
                    $statisticts['diff']
                )
            );

            $message->addInfoMessage(
                sprintf(
                    $this->trans('commands.yaml.diff.messages.equal'),
                    $statisticts['equal']
                )
            );

            return;
        }
        // FLAT YAML file to display full yaml to be used with command yaml:update:key or yaml:update:value
        $diff_flatten = array();
        $key_flatten = '';
        $nested_array->yamlFlattenArray($diff, $diff_flatten, $key_flatten);

        if ($limit !== null) {
            if (!$offset) {
                $offset = 0;
            }
            $diff_flatten = array_slice($diff_flatten, $offset, $limit);
        }

        $table->setHeaders(
            [
                $this->trans('commands.yaml.diff.messages.key'),
                $this->trans('commands.yaml.diff.messages.value'),
            ]
        );

        foreach ($diff_flatten as $yaml_key => $yaml_value) {
            $table->addRow(
                [
                    $yaml_key,
                    $yaml_value
                ]
            );
        }

        $table->render($output);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator_filename = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception(' You must provide a valid file path.');
            }

            return $value;
        };

        $dialog = $this->getDialogHelper();

        // --yaml-left option
        $yaml_left = $input->getArgument('yaml-left');
        if (!$yaml_left) {
            $yaml_left = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.yaml.diff.questions.yaml-left'), ''),
                $validator_filename,
                false,
                null
            );
        }
        $input->setArgument('yaml-left', $yaml_left);

        // --yaml-right option
        $yaml_right = $input->getArgument('yaml-right');
        if (!$yaml_right) {
            $yaml_right = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.yaml.diff.questions.yaml-right'), ''),
                $validator_filename,
                false,
                null
            );
        }
        $input->setArgument('yaml-right', $yaml_right);
    }
}
