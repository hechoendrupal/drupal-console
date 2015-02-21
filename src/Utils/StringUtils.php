<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Utils\StringUtils
 * Utility functions
 */

namespace Drupal\AppConsole\Utils;

use Symfony\Component\Console\Helper\Helper;

class StringUtils extends Helper
{

    // This REGEX captures all uppercase letters after the first character
    const REGEX_UPPER_CASE_LETTERS = '/(?<=\\w)(?=[A-Z])/';
    // This REGEX captures non alphanumeric characters and non underscores
    const REGEX_MACHINE_NAME_CHARS = '@[^a-z0-9_]+@';
    // This REGEX captures
    const REGEX_CAMEL_CASE_UNDER = '/([a-z])([A-Z])/';
    // This REGEX captures spaces around words
    const REGEX_SPACES = '/\s\s+/';
    // This REGEX captures spaces, and comma, and combinations with comma and space *, *
    const REGEX_COMMAS_SPACES = '/[\s,]+/';

    /**
     * Replaces non alphanumeric characters with underscores
     * @param String $name User input
     * @return String $machine_name User input in machine-name format
     */
    public function createMachineName($name)
    {
        $machine_name = preg_replace(self::REGEX_MACHINE_NAME_CHARS, '_', strtolower($name));
        $machine_name = trim($machine_name, '_');

        return $machine_name;
    }

    /**
     *  Converts camel-case strings to machine-name format
     * @param  String $name User input
     * @return String $machine_name  User input in machine-name format
     */
    public function camelCaseToMachineName($name)
    {
        $machine_name = preg_replace(self::REGEX_UPPER_CASE_LETTERS, "_$1", $name);
        $machine_name = preg_replace(self::REGEX_MACHINE_NAME_CHARS, '_', strtolower($machine_name));
        $machine_name = trim($machine_name, '_');

        return $machine_name;
    }

    /**
     * Converts camel-case strings to under-score format
     * @param  String $camel_case User input
     * @return String
     */
    public function camelCaseToUnderscore($camel_case)
    {
        return strtolower(preg_replace(self::REGEX_CAMEL_CASE_UNDER, '$1_$2', $camel_case));
    }

    /**
     * Converts camel-case strings to comma separated format
     * @param  String $camel_case User input
     * @return String
     */
    public function camelCaseToCommaSeparated($camel_case)
    {
        return strtolower(preg_replace(self::REGEX_COMMAS_SPACES, ', ', $camel_case));
    }

    public function getName()
    {
        return "stringUtils";
    }

    public function humanToCamelCase($human)
    {
        return str_replace(' ', '', ucwords($human));
    }

    /**
     * Converts string to lower case, single space, and trims string.
     * @param  String $string User input
     * @return String
     */
    public function camelCaseToLowerCase($string)
    {
        return strtolower(preg_replace(self::REGEX_SPACES, ' ', $string));
    }

    /**
     * Converts the first character of string to Upper case and trims the string.
     * @param  String $string User input
     * @return String
     */
    public function anyCaseToUcFirst($string)
    {
        $string = preg_replace(self::REGEX_SPACES, ' ', $string);
        $string = strtolower($string);
        $string = ucfirst($string);

        return $string;
    }

}
