<?php
/**
 * @file
 * Contains \Drupal\Console\Test\Command\GeneratorContentTypeCommandTest.
 */

namespace Drupal\Console\Test\Command;

use Drupal\Console\Command\GeneratorContentTypeCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Drupal\Console\Test\DataProvider\ContentTypeDataProviderTrait;

class GeneratorContentTypeCommandTest extends GenerateCommandTest
{
    use ContentTypeDataProviderTrait;

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
        $command = new GeneratorContentTypeCommand($this->getTranslatorHelper());
        $command->setContainer($this->getContainer());
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
            ->getMockBuilder('Drupal\Console\Generator\ContentTypeGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();
    }
}
