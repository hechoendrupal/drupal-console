<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\TestCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Test\TestDiscovery;

/**
 * @DrupalCommand(
 *     extension = "simpletest",
 *     extensionType = "module",
 * )
 */
class TestCommand extends Command
{
    /**
      * @var TestDiscovery
      */
    protected $test_discovery;

    /**
     * TestCommand constructor.
     *
     * @param TestDiscovery $test_discovery
     */
    public function __construct(
        TestDiscovery $test_discovery
    ) {
        $this->test_discovery = $test_discovery;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('debug:test')
            ->setDescription($this->trans('commands.debug.test.description'))
            ->addArgument(
                'group',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.test.options.group'),
                null
            )
            ->addOption(
                'test-class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.test.arguments.test-class')
            )
            ->setAliases(['td']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Registers namespaces for disabled modules.
        $this->test_discovery->registerTestNamespaces();

        $testClass = $input->getOption('test-class');
        $group = $input->getArgument('group');

        if ($testClass) {
            $this->testDetail($testClass);
        } else {
            $this->testList($group);
        }
    }

    private function testDetail($test_class)
    {
        $testingGroups = $this->test_discovery->getTestClasses(null);

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
                $testDetails = $this->test_discovery
                    ->getTestInfo($testDetails['name']);
                $testDetails['type'] = 'simpletest';
            }

            $this->getIo()->comment($testDetails['name']);

            $testInfo = [];
            foreach ($testDetails as $key => $value) {
                $testInfo [] = [$key, $value];
            }

            $this->getIo()->table([], $testInfo);

            if ($class) {
                $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                $this->getIo()->info($this->trans('commands.debug.test.messages.methods'));
                foreach ($methods as $method) {
                    if ($method->class == $testDetails['name'] && strpos($method->name, 'test') === 0) {
                        $this->getIo()->simple($method->name);
                    }
                }
            }
        } else {
            $this->getIo()->error($this->trans('commands.debug.test.messages.not-found'));
        }
    }

    protected function testList($group)
    {
        $testingGroups = $this->test_discovery
            ->getTestClasses(null);

        if (empty($group)) {
            $tableHeader = [$this->trans('commands.debug.test.messages.group')];
        } else {
            $tableHeader = [
              $this->trans('commands.debug.test.messages.class'),
              $this->trans('commands.debug.test.messages.type')
            ];

            $this->getIo()->writeln(
                sprintf(
                    '%s: %s',
                    $this->trans('commands.debug.test.messages.group'),
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
        $this->getIo()->table($tableHeader, $tableRows, 'compact');

        if ($group) {
            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.debug.test.messages.success-group'),
                    $group
                )
            );
        } else {
            $this->getIo()->success(
                $this->trans('commands.debug.test.messages.success-groups')
            );
        }
    }
}
