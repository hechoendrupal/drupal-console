<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GenerateEntityBundleCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\Generate\EntityBundleCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\EntityBundleDataProviderTrait;

class GenerateEntityBundleCommandTest extends GenerateCommandTest
{
    use EntityBundleDataProviderTrait;

    /**
     * ContentType generator test
     *
     * @param $module
     * @param $bundle_name
     * @param $bundle_title
     *
     * @dataProvider commandData
     */
    public function testGenerateContentType(
        $module,
        $bundle_name,
        $bundle_title
    ) {
        $command = new EntityBundleCommand($this->getHelperSet());
        $command->setHelperSet($this->getHelperSet());
        $command->setGenerator($this->getGenerator());

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute(
            [
              '--module'         => $module,
              '--bundle-name'    => $bundle_name,
              '--bundle-title'   => $bundle_title
            ],
            ['interactive' => false]
        );

        $this->assertEquals(0, $code);
    }

    private function getGenerator()
    {
        return $this
            ->getMockBuilder('Drupal\Console\Generator\EntityBundleGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
