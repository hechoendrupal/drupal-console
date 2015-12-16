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
use Symfony\Component\Console\Helper\Table;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;

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
        //Registers namespaces for disabled modules.
        $this->getTestDiscovery()->registerTestNamespaces();

        $test_class = $input->getArgument('test-class');
        $group = $input->getOption('group');

        $table = new Table($output);
        $table->setStyle('compact');

        if ($test_class) {
            $this->getTestByID($output, $table, $test_class);
        } else {
            $this->getAllTests($output, $table, $group);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $config_name    String
     */
    private function getTestByID($output, $table, $test_class)
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

            $configurationEncoded = Yaml::encode($test_details);
            $table->addRow([$configurationEncoded]);
            $table->render();

            if ($class) {
                $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                $output->writeln('[+] <info>'. $this->trans('commands.test.debug.messages.methods').'</info>');
                foreach ($methods as $method) {
                    if ($method->class == $test_details['name'] && strpos($method->name, 'test') === 0) {
                        $output->writeln('[-] <info>'. $method->name .'</info>');
                    }
                }
            }
        } else {
            $output->writeln('[+] <error>'. $this->trans('commands.test.debug.messages.not-found').'</error>');
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $config_name    String
     */
    protected function getAllTests($output, $table, $group)
    {
        $testing_groups = $this->getTestDiscovery()->getTestClasses(null);

        $table->setHeaders(
            [
            $this->trans('commands.test.debug.messages.class'),
            $this->trans('commands.test.debug.messages.group'),
            $this->trans('commands.test.debug.messages.type'),
            ]
        );

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
                $table->addRow(array($test['name'], $test['group'], $test['type']));
            }
        }

        $table->render();
    }
}
