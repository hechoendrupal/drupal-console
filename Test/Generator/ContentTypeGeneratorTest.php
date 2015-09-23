<?php

/**
 * @file
 * Contains Drupal\Console\Test\Generator\ContentTypeGeneratorTest.
 */

namespace Drupal\Console\Test\Generator;

use Drupal\Console\Generator\ContentTypeGenerator;
use Drupal\Console\Test\DataProvider\ContentTypeDataProviderTrait;

class ContentTypeGeneratorTest extends GeneratorTest
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
        $generator = new ContentTypeGenerator();
        $this->getHelperSet()->get('renderer')->setSkeletonDirs($this->getSkeletonDirs());
        $this->getHelperSet()->get('renderer')->setTranslator($this->getTranslatorHelper());
        $generator->setHelpers($this->getHelperSet());

        $generator->generate(
            $module,
            $bundle_name,
            $bundle_title
        );

        $files = [
          $generator->getSite()->getModulePath($module) . '/' . $module . '/config/install/core.entity_form_display.node.' . $bundle_name . '.default.yml',
          $generator->getSite()->getModulePath($module) . '/' . $module . '/config/install/core.entity_view_display.node.' . $bundle_name . '.default.yml',
          $generator->getSite()->getModulePath($module) . '/' . $module . '/config/install/core.entity_view_display.node.' . $bundle_name . '.teaser.yml',
          $generator->getSite()->getModulePath($module) . '/' . $module . '/config/install/core.entity_view_mode.node.teaser.yml',
          $generator->getSite()->getModulePath($module) . '/' . $module . '/config/install/field.field.node.' . $bundle_name . '.body.yml',
          $generator->getSite()->getModulePath($module) . '/' . $module . '/config/install/field.storage.node.body.yml'
        ];

        foreach ($files as $file) {
            $this->assertTrue(
                file_exists($file),
                sprintf('%s does not exist', $file)
            );
        }
    }
}
