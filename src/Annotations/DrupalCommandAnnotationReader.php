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
        $annotation = [];
        $reader = new AnnotationReader();
        $drupalCommandAnnotation = $reader->getClassAnnotation(
            new \ReflectionClass($class),
            'Drupal\\Console\\Annotations\\DrupalCommand'
        );
        if ($drupalCommandAnnotation) {
            $annotation['extension'] = $drupalCommandAnnotation->extension?:'';
            $annotation['extensionType'] = $drupalCommandAnnotation->extensionType?:'';
            $annotation['dependencies'] = $drupalCommandAnnotation->dependencies?:[];
        }

        return $annotation;
    }
}
