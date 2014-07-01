<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Utils\StringUtils
 * Utility functions
 */

namespace Drupal\AppConsole\Utils;

class Utils
{

  // This REGEX captures all uppercase letters after the first character
  const REGEX_UPPER_CASE_LETTERS = '/(?<=\\w)(?=[A-Z])/';
  // This REGEX captures non alphanumeric characters and non underscores
  const REGEX_MACHINE_NAME_CHARS = '@[^a-z0-9_]+@';
  // This REGEX captures
  const REGEX_CAMEL_CASE_UNDER = '/([a-z])([A-Z])/';

  /**
   * Replaces non alphanumeric characters with underscores
   * @param String  $name         User input
   * @return String $machine_name User input in machine-name format
   */
  public static function createMachineName($name)
  {
    $machine_name = preg_replace(self::REGEX_MACHINE_NAME_CHARS,'_',strtolower($name));
    $machine_name = trim($machine_name, '_');

    return $machine_name;
  }

  /**
   *  Converts camel-case strings to machine-name format
   *  @param  String $name          User input
   *  @return String $machine_name  User input in machine-name format
   */
  public static function camelCaseToMachineName($name)
  {
    $machine_name = preg_replace(self::REGEX_UPPER_CASE_LETTERS,"_$1", $name);
    $machine_name = preg_replace(self::REGEX_MACHINE_NAME_CHARS,'_',strtolower($machine_name));
    $machine_name = trim($machine_name, '_');

    return $machine_name;
  }

  /**
   *  Converts camel-case strings to under-score format
   *  @param  String $camel_case  User input
   *  @return String
   */
  public static function camelCaseToUnderscore($camel_case)
  {
    return strtolower(preg_replace(self::REGEX_CAMEL_CASE_UNDER, '$1_$2', $camel_case));
  }

}
