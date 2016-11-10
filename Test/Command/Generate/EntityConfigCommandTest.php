<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorEntityConfigCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\EntityConfigCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Test\DataProvider\EntityConfigDataProviderTrait;

class EntityConfigCommandTest extends GenerateCommandTest
{
    use EntityConfigDataProviderTrait;

    /**
     * EntityConfig generator test
     *
     * @param $module
     * @param $entity_name
     * @param $entity_class
     * @param $label
     * @param $base_path
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityConfig(
        $module,
        $entity_name,
        $entity_class,
        $label,
        $base_path
    ) {
        $generator = an::entityConfigGenerator();
        $manager = an::extensionManager();
        $command = new EntityConfigCommand(
          $manager,
          $generator->reveal(),
          new Validator($manager),
          new StringConverter()
        );
        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--entity-name'    => $entity_name,
              '--entity-class'   => $entity_class,
              '--label'          => $label,
              '--base-path'      => $base_path,
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
