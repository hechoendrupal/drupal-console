<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\TestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;

class TestDebugCommand extends ContainerAwareCommand
{

    /**
      * {@inheritdoc}
      */
    protected function configure()
    {
        $this
        ->setName('test:debug')
        ->setDescription($this->trans('commands.test.debug.description'))
        ->addArgument('test-id', InputArgument::OPTIONAL,
          $this->trans('commands.test.debug.arguments.resource-id'))
        ->addOption('group', '', InputOption::VALUE_OPTIONAL,
          $this->trans('commands.test.debug.options.group'))
        ;

        $this->addDependency('simpletest');
    }

    /**
      * {@inheritdoc}
      */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $test_id = $input->getArgument('test-id');
        $group = $input->getOption('group');

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        if ($test_id) {
            $this->getTestByID($output, $table, $test_id);
        } else {
            $this->getAllTests($output, $table, $group);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $config_name    String
     */
    private function getTestByID($output, $table, $test_id)
    {
        $testing_groups = $this->getTestDiscovery()->getTestClasses(null);

        foreach ($testing_groups as $testing_group => $tests) {
            foreach ($tests as $key => $test) {
                break;
            }
        }

        $configurationEncoded = Yaml::encode($test);
        $table->addRow([$configurationEncoded]);
        $table->render($output);
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
            $this->trans('commands.test.debug.messages.id'),
            $this->trans('commands.test.debug.messages.group'),
            ]);

        foreach ($testing_groups as $testing_group => $tests) {
            if(!empty($group) && $group != $testing_group) {
                continue;
            }

            foreach ($tests as $test) {
                $table->addRow(array($test['name'], $test['group']));
            }
        }

        $table->render($output);
      }
  }
