<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ArrayInputTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class ArrayInputTrait
 *
 * @package Drupal\Console\Command
 */
trait ArrayInputTrait
{
    /**
     * Parse strings to array '"key":"value","key1":"value1"'.
     *
     * @param string $inlineInputs
     *   Input from the user.
     * @return array
     *   Input array.
     */
    public function explodeInlineArray($inlineInputs)
    {
        $inputs = [];
        foreach ($inlineInputs as $inlineInput) {
            $explodeInput = explode('|', $inlineInput);
            $parameters = [];
            foreach ($explodeInput as $inlineParameter) {
                $inlineParameter = trim($inlineParameter);
                list($key, $value) = explode('":"', $inlineParameter);
                $key = rtrim(ltrim($key, '"'), '"');
                $value = rtrim(ltrim($value, '"'), '"');
                if (!empty($value)) {
                    $parameters[$key] = $value;
                }
            }

            // Remove options data if type isn't in the list of allowed types
            if($parameters['options'] && !in_array($parameters['type'], ['checkboxes', 'radios', 'select'])){
                $parameters['options'] = [];
            }
            $inputs[] = $parameters;
        }

        return $inputs;
    }
}
