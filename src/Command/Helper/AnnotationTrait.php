<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\AnnotationTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Output\OutputInterface;

trait AnnotationTrait
{
    /**
     * @param OutputInterface $output
     * @param DialogHelper $dialog
     * @return string
     */
    public function annotationQuestion(OutputInterface $output, DialogHelper $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion(
                $this->trans('commands.common.questions.annotation.class'),
                $default = $this->getStringUtils()->humanToCamelCase('DefaultAnnotation'),
                $sep = '?'
            ),
            function($annotation) {
                $annotation_class = $this->getStringUtils()->humanToCamelCase($annotation);
                if ($annotation_class != $annotation) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            $this->trans('commands.common.questions.annotation.invalid_class'),
                            $annotation
                        )
                    );
                }

                return $annotation;
            },
            $attempts = false,
            $default = 'DefaultAnnotation',
            $autocomplete = null
        );
    }

    /**
     * @param OutputInterface $output
     * @param DialogHelper $dialog
     * @return array
     */
    public function annotationPropertyQuestion(OutputInterface $output, DialogHelper $dialog)
    {
        if ($this->hasConfirmed($output, $dialog)) {
            $properties = [];
            while(true) {
                $property_name = $this->askPropertyName($output, $dialog);

                if(empty($property_name)){
                    break;
                }

                $property_type = $this->askPropertyType($output, $dialog);
                array_push($properties, 'name: ' . $property_name . ', type: ' . $property_type);
            }

            return $properties;
        }
    }

    /**
     * @param OutputInterface $output
     * @param DialogHelper $dialog
     * @return bool
     */
    protected function hasConfirmed(OutputInterface $output, DialogHelper $dialog)
    {
        return $dialog->askConfirmation(
            $output,
            $dialog->getQuestion(
                $question = $this->trans('commands.common.questions.annotation.confirm'),
                $default = 'yes',
                $sep = '?'
            ),
            $default = true
        );
    }

    /**
     * @param OutputInterface $output
     * @param DialogHelper $dialog
     * @return string
     */
    protected function askPropertyName(OutputInterface $output, DialogHelper $dialog)
    {
        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion(
                $question = '  ' . $this->trans('commands.common.questions.annotation.property'),
                $default = null,
                $sep = ':'
            ),
            $validator = function($input) {
                return $this->getStringUtils()->createMachineName($input);
            },
            $attempts = false,
            $default = null,
            $autocomplete = null
        );
    }

    /**
     * @param OutputInterface $output
     * @param DialogHelper $dialog
     * @return string
     */
    protected function askPropertyType(OutputInterface $output, DialogHelper $dialog)
    {
        $types = $this->getPropertyTypes();
        $type = $dialog->askAndValidate(
            $output,
            $dialog->getQuestion(
                $question = '  ' .$this->trans('commands.common.questions.annotation.property_type'),
                $default = null,
                $sep = ':'
            ),
            $validator = function($type) use ($types) {
                if (!in_array($type, $types)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            $this->trans('commands.common.questions.annotation.invalid_type'),
                            $type
                        )
                    );
                }
                return $type;
            },
            $attempts = false,
            $default = null,
            $autocomplete = $this->getPropertyTypes()
        );

        return $type;
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function buildPropertiesAnnotation(array $properties)
    {
        $variables = [];
        foreach($properties as $property) {
            $separate = explode(',', $property);

            if(count($separate) != 2) {
                throw new \InvalidArgumentException(
                    $this->trans('commands.common.questions.annotation.property_format')
                );
            }

            $name = explode(':', $separate[0]);
            $type = explode(':', $separate[1]);

            if(trim($name[0]) != 'name') {
                throw new \InvalidArgumentException(
                    $this->trans('commands.common.questions.annotation.property_format')
                );
            }

            if(trim($type[0]) != 'type') {
                throw new \InvalidArgumentException(
                    $this->trans('commands.common.questions.annotation.property_format')
                );
            }

            array_push($variables, [
                'name' => $name[1],
                'type' => $type[1],
            ]);
        }

        return $variables;
    }

    /**
     * @return array
     */
    protected function getPropertyTypes()
    {
        return [
            'string',
            'translation',
            'mixed',
            'float',
            'integer',
            'array',
            'bool',
        ];
    }

    /**
     * @return \Drupal\AppConsole\Utils\StringUtils
     */
    abstract public function getStringUtils();

    /**
     * @return string
     */
    abstract public function trans($key);

}