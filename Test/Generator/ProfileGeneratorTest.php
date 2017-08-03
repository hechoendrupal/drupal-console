<?php
/**
 * @file
 * Contains Drupal\Console\Test\Generator\ProfileGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\ProfileGenerator;
use Drupal\Console\Test\DataProvider\ProfileDataProviderTrait;

/**
 * Class ProfileGeneratorTest
 * @package Drupal\Console\Test\Generator
 */
class ProfileGeneratorTest extends GeneratorTest
{
    use ProfileDataProviderTrait;

    /**
     * Profile generator test.
     *
     * @param $profile
     * @param $machine_name
     * @param $profile_path
     * @param $description
     * @param $core
     * @param $dependencies
     * @param $distribution
     *
     * @dataProvider commandData
     */
    public function testGenerateProfile(
        $profile,
        $machine_name,
        $profile_path,
        $description,
        $core,
        $dependencies,
        $themes,
        $distribution
    ) {
        $generator = new ProfileGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $profile,
            $machine_name,
            $profile_path,
            $description,
            $core,
            $dependencies,
            $themes,
            $distribution
        );

        $files = [
            $machine_name . '.info.yml',
            $machine_name . '.install',
            $machine_name . '.profile',
        ];

        foreach ($files as $file) {
            $file_path = $profile_path . '/' . $machine_name . '/' . $file;
            $this->assertTrue(
                file_exists($file_path),
                sprintf('%s has been generated', $file_path)
            );
        }
    }

}
