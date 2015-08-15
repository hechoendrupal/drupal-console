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
use Drupal\simpletest\TestDiscovery;

class TestDebugCommand extends ContainerAwareCommand
{
    protected $exceptions;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('test:debug')
            ->setDescription($this->trans('commands.test.debug.description'))
            ->addArgument(
                'test-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.test.debug.arguments.resource-id')
            )
            ->addOption(
                'group',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.test.debug.options.group')
            );

        $this->addDependency('simpletest');

        // Some tests that cannot be debug if module dependency is enabled, logic is Test => Module
        $this->exceptions = array(
            'Drupal\action\Tests\Migrate\d6\MigrateActionConfigsTest' => 'migrate_drupal',
            'Drupal\block_content\Tests\Migrate\d6\MigrateBlockContentTest' => 'migrate_drupal',
            'Drupal\contact\Tests\Migrate\d6\MigrateContactCategoryTest' => 'migrate_drupal',
            'Drupal\contact\Tests\Migrate\d6\MigrateContactConfigsTest' => 'migrate_drupal',
            'Drupal\dblog\Tests\Migrate\d6\MigrateDblogConfigsTest' => 'migrate_drupal',
            'Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase' => 'migrate_drupal',
            'Drupal\dblog\Tests\Migrate\d7\MigrateDblogConfigsTest' => 'migrate_drupal',
            'Drupal\filter\Tests\Migrate\d6\MigrateFilterFormatTest' => 'migrate_drupal',
            'Drupal\menu_link_content\Tests\Migrate\d6\MigrateMenuLinkTest' => 'migrate_drupal',
            'Drupal\menu_ui\Tests\Migrate\d6\MigrateMenuConfigsTest' => 'migrate_drupal',
            'Drupal\node\Tests\Migrate\d6\MigrateNodeBundleSettingsTest' => 'migrate_drupal',
            'Drupal\node\Tests\Migrate\d6\MigrateNodeConfigsTest' => 'migrate_drupal',
            'Drupal\node\Tests\Migrate\d6\MigrateNodeRevisionTest' => 'migrate_drupal',
            'Drupal\node\Tests\Migrate\d6\MigrateNodeTest' => 'migrate_drupal',
            'Drupal\node\Tests\Migrate\d6\MigrateNodeTypeTest' => 'migrate_drupal',
            'Drupal\node\Tests\Migrate\d6\MigrateViewModesTest' => 'migrate_drupal',
            'Drupal\path\Tests\Migrate\d6\MigrateUrlAliasTest' => 'migrate_drupal',
            'Drupal\block\Tests\Migrate\d6\MigrateBlockTest' => 'migrate_drupal',
            'Drupal\search\Tests\Migrate\d6\MigrateSearchConfigsTest' => 'migrate_drupal',
            'Drupal\search\Tests\Migrate\d6\MigrateSearchPageTest' => 'migrate_drupal',
            'Drupal\simpletest\Tests\Migrate\d6\MigrateSimpletestConfigsTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateDateFormatTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateMenuTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemCronTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemFileTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemImageGdTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemImageTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemLoggingTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemMaintenanceTest' => 'migeate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemPerformanceTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemRssTest' => 'migrate_drupal',
            'Drupal\system\Tests\Migrate\d6\MigrateSystemSiteTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateTaxonomyConfigsTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateTaxonomyTermTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateTaxonomyVocabularyTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateTermNodeRevisionTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateTermNodeTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyEntityDisplayTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyEntityFormDisplayTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyFieldInstanceTest' => 'migrate_drupal',
            'Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyFieldTest' => 'migrate_drupal',
            'Drupal\text\Tests\Migrate\d6\MigrateTextConfigsTest' => 'migrate_drupal',
            'Drupal\update\Tests\Migrate\d6\MigrateUpdateConfigsTest' => 'migrate_drupal',
            'Drupal\block_content\Tests\BlockContentTranslationUITest' => 'content_translation',
            'Drupal\comment\Tests\CommentTranslationUITest' => 'content_translation',
            'Drupal\menu_link_content\Tests\MenuLinkContentTranslationUITest' => 'content_translation',
            'Drupal\node\Tests\NodeTranslationUITest' => 'content_translation',
            'Drupal\shortcut\Tests\ShortcutTranslationUITest' => 'content_translation',
            'Drupal\taxonomy\Tests\TermTranslationUITest' => 'content_translation',
            'Drupal\user\Tests\UserTranslationUITest' => 'content_translation',
            'Drupal\system\Tests\Session\SessionAuthenticationTest' => 'basic_auth'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $test_name = $input->getArgument('test-id');
        $group = $input->getOption('group');

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        if ($test_name) {
            $this->getTestByID($output, $table, $test_name);
        } else {
            $this->getAllTests($output, $table, $group);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $config_name    String
     */
    private function getTestByID($output, $table, $test_name)
    {
        $testing_groups = $this->getTestDiscovery()->getTestClasses(null);

        $test_details = null;
        foreach ($testing_groups as $testing_group => $tests) {
            foreach ($tests as $key => $test) {
                if($test['name'] == $test_name) {
                    $test_details = $test;
                    break;
                }
            }
            if($test_details != null) {
                break;
            }
        }

        $class = null;
        if($test_details) {
            if ($dependency = $this->checkExceptions($test['name'])) {
                $test_details['error'] = $this->trans('commands.test.debug.messages.missing-dependency') .  ' ' . $dependency;
            }
            else {
                if (is_subclass_of($test_details['name'], 'PHPUnit_Framework_TestCase')) {
                    $test_details['type'] = 'phpunit';
                } else {
                    $test_details = $this->getTestDiscovery()->getTestInfo($test_details['name']);
                    $test_details['type'] = 'simpletest';
                    $class = new \ReflectionClass($test['name']);
                }
            }
            $configurationEncoded = Yaml::encode($test_details);
            $table->addRow([$configurationEncoded]);
            $table->render($output);

            if($class) {
                $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                $output->writeln('[+] <info>'. $this->trans('commands.test.debug.messages.methods').'</info>');
                foreach ($methods as $method ) {
                    if($method->class == $test_details['name']) {
                        $output->writeln('[-] <info>'. $method->name .'</info>');
                    }
                }

            }


        }
        else {
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
            $this->trans('commands.test.debug.messages.id'),
            $this->trans('commands.test.debug.messages.group'),
            $this->trans('commands.test.debug.messages.type'),
            ]
        );

        foreach ($testing_groups as $testing_group => $tests) {
            if (!empty($group) && $group != $testing_group) {
                continue;
            }

            foreach ($tests as $test) {
                // Avoid process tests that will produce a fatal error due missing dependencies
                if ($dependency = $this->checkExceptions($test['name'])) {
                    $test['type'] = $this->trans('commands.test.debug.messages.missing-dependency') .  ' ' . $dependency;
                }
                else {
                    if (is_subclass_of($test['name'], 'PHPUnit_Framework_TestCase')) {
                        $test['type'] = 'phpunit';
                    } else {
                        $test['type'] = 'simpletest';
                    }
                }
                $table->addRow(array($test['name'], $test['group'], $test['type']));
            }
        }

        $table->render($output);
    }

    protected function checkExceptions($test_name) {
        $module_handler = $this->getModuleHandler();

        if(array_key_exists($test_name, $this->exceptions)) {
            if(!$module_handler->moduleExists($this->exceptions[$test_name])) {
                return $this->exceptions[$test_name];
            }
            else {
                return false;
            }
        }

        return false;
    }
}
