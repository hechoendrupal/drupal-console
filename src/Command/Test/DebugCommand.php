<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Test\DebugCommand.
 */

namespace Drupal\Console\Command\Test;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Test
 */
class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('test:debug')
            ->setDescription($this->trans('commands.test.debug.description'))
            ->addArgument(
                'test-class',
                InputArgument::OPTIONAL,
                $this->trans('commands.test.debug.arguments.test-class')
            )
            ->addOption(
                'group',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.test.debug.options.group')
            );

        $this->addDependency('simpletest');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        //Registers namespaces for disabled modules.
        $this->getTestDiscovery()->registerTestNamespaces();

        $test_class = $input->getArgument('test-class');
        $group = $input->getOption('group');

        if ($test_class) {
            $this->testDetail($io, $test_class);
        } else {
            $this->testList($io, $group);
        }
    }

    private function testDetail(DrupalStyle $io, $test_class)
    {
        $testing_groups = $this->getTestDiscovery()->getTestClasses(null);

        $test_details = null;
        foreach ($testing_groups as $testing_group => $tests) {
            foreach ($tests as $key => $test) {
                if ($test['name'] == $test_class) {
                    $test_details = $test;
                    break;
                }
            }
            if ($test_details !== null) {
                break;
            }
        }

        $class = null;
        if ($test_details) {
            $class = new \ReflectionClass($test['name']);
            if (is_subclass_of($test_details['name'], 'PHPUnit_Framework_TestCase')) {
                $test_details['type'] = 'phpunit';
            } else {
                $test_details = $this->getTestDiscovery()->getTestInfo($test_details['name']);
                $test_details['type'] = 'simpletest';
            }

            $io->comment($test_details['name']);

            $test_info = [];
            foreach ($test_details as $key => $value) {
                $test_info [] = [$key, $value];
            }

            $io->table([], $test_info);

            if ($class) {
                $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                $io->info($this->trans('commands.test.debug.messages.methods'));
                foreach ($methods as $method) {
                    if ($method->class == $test_details['name'] && strpos($method->name, 'test') === 0) {
                        $io->simple($method->name);
                    }
                }
            }
        } else {
            $io->error($this->trans('commands.test.debug.messages.not-found'));
        }
    }

    protected function testList(DrupalStyle $io, $group)
    {
        $testing_groups = $this->getTestDiscovery()->getTestClasses(null);

        $tableHeader = [
          $this->trans('commands.test.debug.messages.class'),
          $this->trans('commands.test.debug.messages.group'),
          $this->trans('commands.test.debug.messages.type')
        ];

        $tableRows = [];
        foreach ($testing_groups as $testing_group => $tests) {
            if (!empty($group) && $group != $testing_group) {
                continue;
            }

            foreach ($tests as $test) {
                if (is_subclass_of($test['name'], 'PHPUnit_Framework_TestCase')) {
                    $test['type'] = 'phpunit';
                } else {
                    $test['type'] = 'simpletest';
                }
                $tableRows[] =[
                  $test['name'],
                  $test['group'],
                  $test['type']
                ];
            }
        }
        $io->table($tableHeader, $tableRows, 'compact');
    }
}
