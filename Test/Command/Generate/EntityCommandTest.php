<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorEntityCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\EntityConfigCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Test\DataProvider\EntityDataProviderTrait;

class EntityCommandTest extends GenerateCommandTest
{
    use EntityDataProviderTrait;

    /**
     * EntityConfig generator test
     *
     * @param $module
     * @param $entity_class
     * @param $entity_name
     * @param $label
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityConfig(
        $module,
        $entity_class,
        $entity_name,
        $label,
        $base_path
    ) {
      $generator = an::entityConfigGenerator();
      $manager = an::extensionManager();
      $command = new EntityConfigCommand(
        $manager,
        $generator->reveal(),
        new Validator($manager),
        new StringConverter(),
        new StringConverter()
      );

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--entity-class'   => $entity_class,
              '--entity-name'    => $entity_name,
              '--label'          => $label,
              '--base-path'      => $base_path
            ],
            ['interactive' => false]
        );
        $generator
          ->generate($module, $entity_name, $entity_class, $label, $base_path, $bundle_of=false)
          ->shouldHaveBeenCalled()
        ;
        $this->assertEquals(0, $code);
    }
  }
