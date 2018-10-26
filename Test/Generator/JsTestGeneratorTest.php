<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\JsTestGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\JsTestGenerator;
use Drupal\Console\Test\DataProvider\JsTestDataProviderTrait;

class JsTestGeneratorTest extends GeneratorTest
{
    use JsTestDataProviderTrait;

    /**
     * JavaScript test generator test
     *
     * @param $module
     * @param $class_name
     *
     * @dataProvider commandData
     */
    public function testGenerateJsTest(
        $module,
        $class_name
    ) {
        $generator = new JsTestGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $class_name
        );

        $files = [
          $generator->getSite()->getJsTestsPath($module) . "/$class_name.php",
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
