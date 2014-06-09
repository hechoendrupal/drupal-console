<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Utils\Utils
 * Utility functions
 */

namespace Drupal\AppConsole\Utils;

class Utils
{

  // This REGEX captures all uppercase letters after the first character
  const REGEX_UPPER_CASE_LETTERS = '/(?<=\\w)(?=[A-Z])/';
  // This REGEX captures non alphanumeric characters and non underscores
  const REGEX_MACHINE_NAME_CHARS = '@[^a-z0-9_]+@';

  /**
   * Replaces non alphanumeric characters with underscores
   * @param String $input User input
   * @return String $machine_name User input in machine-name format
   */
  public static function createMachineName($input)
  {
    $machine_name = preg_replace(self::REGEX_MACHINE_NAME_CHARS,'_',strtolower($input));
    $machine_name = trim($machine_name, '_');
    
    return $machine_name;
  }

  /**
   *  Converts camel-case strings to machine-name format
   *  @param String $name User input
   *  @return String $machine_name User input in machine-name format
   */
  public static function camelCaseToMachineName($camel_case)
  {
  	$machine_name = preg_replace(self::REGEX_UPPER_CASE_LETTERS,"_$1", $camel_case);
    $machine_name = preg_replace(self::REGEX_MACHINE_NAME_CHARS,'_',strtolower($machine_name));
    $machine_name = trim($machine_name, '_');
    
    return $machine_name;
  }

}