<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorEntityContentCommandTest.
 */

namespace Drupal\Console\Test\Command\Generate;

use Drupal\Console\Command\Generate\EntityContentCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Test\Builders\a as an;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Test\DataProvider\EntityContentDataProviderTrait;

class EntityContentCommandTest extends GenerateCommandTest
{
    use EntityContentDataProviderTrait;

    /**
     * EntityContent generator test
     *
     * @param $module
     * @param $entity_name
     * @param $entity_class
     * @param $label
     * @param $base_path
     *
     * @dataProvider commandData
     */
    public function testGenerateEntityContent(
        $module,
        $entity_name,
        $entity_class,
        $label,
        $base_path
    ) {
        $generator = an::entityContentGenerator();
        $manager = an::extensionManager();
        $command = new EntityContentCommand(
          new chainQueue(),
          $generator->reveal(),
          new StringConverter(),
          $manager,
          new Validator($manager)
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

        $this->assertEquals(0, $code);
    }
}
