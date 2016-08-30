<?php

namespace Drupal\Console\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class DrupalCommandReader
 * @package Drupal\Console\Annotations
 */
class DrupalCommandAnnotationReader {

    protected $serviceDefinitions;

    /**
     * setServiceDefinitions.
     * @param $serviceDefinitions
     */
    public function setServiceDefinitions($serviceDefinitions) {
        $this->serviceDefinitions = $serviceDefinitions;
    }

    /**
     * @param $name
     * @return array
     */
    public function readAnnotations($name)
    {
        $annotation = [];
        if (!$serviceDefinition = $this->serviceDefinitions[$name]) {
            return $annotation;
        }
        $reader = new AnnotationReader();
        $drupalCommandAnnotation = $reader->getClassAnnotation(
            new \ReflectionClass($serviceDefinition->getClass()),
            'Drupal\\Console\\Annotations\\DrupalCommand'
        );
        if($drupalCommandAnnotation) {
            $annotation['extension'] = $drupalCommandAnnotation->extension?:'';
            $annotation['dependencies'] = $drupalCommandAnnotation->dependencies?:[];
        }

        return $annotation;
    }

}