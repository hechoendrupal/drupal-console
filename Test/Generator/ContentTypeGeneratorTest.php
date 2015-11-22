<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\EntityBundleGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\EntityBundleGenerator;
use Drupal\Console\Test\DataProvider\EntityBundleDataProviderTrait;

class ContentTypeGeneratorTest extends GeneratorTest
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
        $generator = new EntityBundleGenerator();
        $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
        $this->getRenderHelper()->setTranslator($this->getTranslatorHelper());
        $generator->setHelperSet($this->getHelperSet());

        $generator->generate(
            $module,
            $bundle_name,
            $bundle_title
        );

        $files = [
          $generator->getSite()->getModulePath($module) . '/config/install/core.entity_form_display.node.' . $bundle_name . '.default.yml',
          $generator->getSite()->getModulePath($module) . '/config/install/core.entity_view_display.node.' . $bundle_name . '.default.yml',
          $generator->getSite()->getModulePath($module) . '/config/install/core.entity_view_display.node.' . $bundle_name . '.teaser.yml',
          $generator->getSite()->getModulePath($module) . '/config/install/field.field.node.' . $bundle_name . '.body.yml',
          $generator->getSite()->getModulePath($module) . '/config/install/node.type.' . $bundle_name . '.yml',
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
