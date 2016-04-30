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
                'group',
                InputArgument::OPTIONAL,
                $this->trans('commands.test.debug.options.group'),
                null
            )
            ->addOption(
                'test-class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.test.debug.arguments.test-class')
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

        $testClass = $input->getOption('test-class');
        $group = $input->getArgument('group');

        if ($testClass) {
            $this->testDetail($io, $testClass);
        } else {
            $this->testList($io, $group);
        }
    }

    private function testDetail(DrupalStyle $io, $test_class)
    {
        $testingGroups = $this->getTestDiscovery()->getTestClasses(null);

        $testDetails = null;
        foreach ($testingGroups as $testing_group => $tests) {
            foreach ($tests as $key => $test) {
                if ($test['name'] == $test_class) {
                    $testDetails = $test;
                    break;
                }
            }
            if ($testDetails !== null) {
                break;
            }
        }

        $class = null;
        if ($testDetails) {
            $class = new \ReflectionClass($test['name']);
            if (is_subclass_of($testDetails['name'], 'PHPUnit_Framework_TestCase')) {
                $testDetails['type'] = 'phpunit';
            } else {
                $testDetails = $this->getTestDiscovery()->getTestInfo($testDetails['name']);
                $testDetails['type'] = 'simpletest';
            }

            $io->comment($testDetails['name']);

            $testInfo = [];
            foreach ($testDetails as $key => $value) {
                $testInfo [] = [$key, $value];
            }

            $io->table([], $testInfo);

            if ($class) {
                $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                $io->info($this->trans('commands.test.debug.messages.methods'));
                foreach ($methods as $method) {
                    if ($method->class == $testDetails['name'] && strpos($method->name, 'test') === 0) {
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
        $testingGroups = $this->getTestDiscovery()->getTestClasses(null);

        if (empty($group)) {
            $tableHeader = [$this->trans('commands.test.debug.messages.group')];
        } else {
            $tableHeader = [
                $this->trans('commands.test.debug.messages.class'),
                $this->trans('commands.test.debug.messages.type')
            ];

            $io->writeln(
                sprintf(
                    '%s: %s',
                    $this->trans('commands.test.debug.messages.group'),
                    $group
                )
            );
        }

        $tableRows = [];
        foreach ($testingGroups as $testing_group => $tests) {
            if (empty($group)) {
                $tableRows[] =[$testing_group];
                continue;
            }

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
                  $test['type']
                ];
            }
        }
        $io->table($tableHeader, $tableRows, 'compact');

        if ($group) {
            $io->success(
                sprintf(
                    $this->trans('commands.test.debug.messages.success-group'),
                    $group
                )
            );
        } else {
            $io->success(
                $this->trans('commands.test.debug.messages.success-groups')
            );
        }
    }
}
