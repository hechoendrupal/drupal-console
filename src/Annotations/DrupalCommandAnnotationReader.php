<?php

namespace Drupal\Console\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class DrupalCommandReader
 *
 * @package Drupal\Console\Annotations
 */
class DrupalCommandAnnotationReader
{
    /**
     * @param $class
     * @return array
     */
    public function readAnnotation($class)
    {
        $annotation = [
            'extension' => null,
            'extensionType' => null,
            'dependencies' => [],
            'bootstrap' => 'installed'
        ];
        $reader = new AnnotationReader();
        $drupalCommandAnnotation = $reader->getClassAnnotation(
            new \ReflectionClass($class),
            'Drupal\\Console\\Annotations\\DrupalCommand'
        );
        if ($drupalCommandAnnotation) {
            $annotation['extension'] = $drupalCommandAnnotation->extension?:null;
            $annotation['extensionType'] = $drupalCommandAnnotation->extensionType?:null;
            $annotation['dependencies'] = $drupalCommandAnnotation->dependencies?:[];
            $annotation['bootstrap'] = $drupalCommandAnnotation->bootstrap?:'install';
        }

        return $annotation;
    }
}
